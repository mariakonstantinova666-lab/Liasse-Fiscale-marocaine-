<?php

namespace App\Services;

use App\Models\LiasseData;
use App\Models\Societe;
use DOMDocument;
use DOMElement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Genere le XML EDI SIMPL-IS a partir de la liasse deja preparee.
 *
 * Le service reste orchestrateur : il execute le moteur de controle, transforme
 * les donnees applicatives vers les codes DGI connus, puis produit le XML.
 */
class EdiXmlGeneratorService
{
    private array $calculatedMappingMissing = [];

    public function __construct(
        private BalanceService $balanceService,
        private LiasseControlService $controlService,
        private EdiCalculatedTableExportService $calculatedExporter
    ) {
    }

    /**
     * @return array{path:string, filename:string, controls:array<int, array<string, mixed>>, warnings:int, mapped_values:int}
     */
    public function generate(int $userId, int $exercice): array
    {
        $context = $this->context($userId, $exercice);
        $blocking = collect($context['controls'])->filter(fn ($rule) => $rule['bloquant'] && !$rule['ok']);

        if ($blocking->isNotEmpty()) {
            throw new EdiGenerationException(
                'La generation EDI est impossible tant que des erreurs bloquantes subsistent.',
                $blocking->values()->all(),
                $context['controls']
            );
        }

        $mappedValues = $this->buildMappedValues($context);
        if ($mappedValues === []) {
            throw new EdiGenerationException(
                'La generation EDI est impossible : aucun champ de liasse ne correspond au catalogue DGI connu.',
                [[
                    'id' => 'EDI_NO_MAPPED_VALUES',
                    'titre' => 'Aucun champ EDI exploitable',
                    'ok' => false,
                    'ecart' => 1,
                    'message' => 'Les donnees de liasse existent peut-etre, mais aucun code EDI DGI connu ne peut etre alimente.',
                    'bloquant' => true,
                    'severity' => 'Erreur',
                    'tableau' => 'EDI',
                    'rubrique' => 'Mapping des cellules',
                    'regle' => 'Chaque valeur XML doit etre rattachee a un codeEdi DGI.',
                    'suggestion' => 'Completer le mapping EDI ou importer les tableaux sources declares comme obligatoires.',
                ]],
                $context['controls']
            );
        }

        $validationErrors = $this->validateMappedValues($context, $mappedValues);
        if ($validationErrors !== []) {
            throw new EdiGenerationException(
                'La generation EDI est impossible : la validation du contenu XML a detecte des ecarts.',
                $validationErrors,
                array_merge($context['controls'], $validationErrors)
            );
        }

        $xml = $this->buildXml($context, $mappedValues);

        $filename = sprintf('liasse_edi_dgi_%s_%s.xml', $exercice, now()->format('Ymd_His'));
        $relativePath = 'edi/'.$userId.'/'.$filename;
        Storage::disk('local')->put($relativePath, $xml);

        return [
            'path' => Storage::disk('local')->path($relativePath),
            'filename' => $filename,
            'controls' => $context['controls'],
            'warnings' => collect($context['controls'])->filter(fn ($rule) => !$rule['ok'] && !$rule['bloquant'])->count(),
            'mapped_values' => count($mappedValues),
        ];
    }

    /**
     * @return array{societe:?Societe, user_id:int, items:Collection, itemsPrev:Collection, liasseData:Collection, controls:array<int, array<string, mixed>>, exercice:int}
     */
    public function context(int $userId, int $exercice): array
    {
        [$items, $itemsPrev] = $this->balanceService->lignesAvecPrecedent($userId, $exercice);
        $liasseData = LiasseData::query()
            ->where('user_id', $userId)
            ->where('exercice', $exercice)
            ->orderBy('tableau_code')
            ->orderBy('cle')
            ->get();

        return [
            'societe' => Societe::query()->where('user_id', $userId)->first(),
            'user_id' => $userId,
            'items' => $items,
            'itemsPrev' => $itemsPrev,
            'liasseData' => $liasseData,
            'controls' => $this->controlService->verifierLiasse($items, $liasseData, $itemsPrev),
            'exercice' => $exercice,
        ];
    }

    /**
     * @param array<string, mixed> $context
     * @param array<int, array{tableau:int, code:int, valeur:string, ligne:?int}> $mappedValues
     */
    private function buildXml(array $context, array $mappedValues): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $root = $dom->appendChild($dom->createElement('Liasse'));
        $this->appendModele($dom, $root);
        $this->appendSociete($dom, $root, $context);
        $this->appendResultatFiscal($dom, $root, $context);
        $this->appendGroupeValeursTableau($dom, $root, $mappedValues);

        $xml = $dom->saveXML();
        if ($xml === false) {
            throw new RuntimeException('Impossible de serialiser le fichier XML EDI.');
        }

        return $xml;
    }

    private function appendModele(DOMDocument $dom, DOMElement $root): void
    {
        $modele = $root->appendChild($dom->createElement('modele'));
        $this->appendTextElement($dom, $modele, 'id', (string) config('edi.modele_id', 7));
    }

    /**
     * @param array<string, mixed> $context
     */
    private function appendSociete(DOMDocument $dom, DOMElement $root, array $context): void
    {
        /** @var Societe|null $societe */
        $societe = $context['societe'] ?? null;
        $exercice = (int) ($context['exercice'] ?? now()->year);
        $node = $root->appendChild($dom->createElement('societe'));

        $this->appendTextElement($dom, $node, 'raisonSociale', $societe?->nom_societe);
        $this->appendTextElement($dom, $node, 'identifiantFiscal', $societe?->if);
        $this->appendTextElement($dom, $node, 'ice', $societe?->ice);
        $this->appendTextElement($dom, $node, 'registreCommerce', $societe?->rc);
        $this->appendTextElement($dom, $node, 'cnss', $societe?->cnss);
        $this->appendTextElement($dom, $node, 'patente', $societe?->patente);
        $this->appendTextElement($dom, $node, 'adresse', $societe?->adresse);
        $this->appendTextElement($dom, $node, 'exerciceDu', sprintf('01/01/%d', $exercice));
        $this->appendTextElement($dom, $node, 'exerciceAu', sprintf('31/12/%d', $exercice));
    }

    /**
     * @param array<string, mixed> $context
     */
    private function appendResultatFiscal(DOMDocument $dom, DOMElement $root, array $context): void
    {
        $data = $this->indexedLiasseData($context['liasseData']);
        $resultatComptable = $this->resultatComptable($context['items']);
        $benefice = max(0.0, $resultatComptable);
        $perte = max(0.0, -$resultatComptable);
        $reintegrations = $this->numberFromData($data, 'passage_fiscal', 'reintegrations_courantes_total')
            + $this->numberFromData($data, 'passage_fiscal', 'reintegrations_non_courantes_total');
        $deductions = $this->numberFromData($data, 'passage_fiscal', 'deductions_courantes_total')
            + $this->numberFromData($data, 'passage_fiscal', 'deductions_non_courantes_total');
        $reports = $this->numberFromData($data, 'passage_fiscal', 'reports_deficitaires_total');

        $this->appendTextElement($dom, $root, 'resultatFiscal', $this->formatAmount($benefice - $perte + $reintegrations - $deductions - $reports));
    }

    /**
     * @param array<int, array{tableau:int, code:int, valeur:string, ligne:?int}> $mappedValues
     */
    private function appendGroupeValeursTableau(DOMDocument $dom, DOMElement $root, array $mappedValues): void
    {
        $group = $root->appendChild($dom->createElement('groupeValeursTableau'));

        foreach (collect($mappedValues)->groupBy('tableau')->sortKeys() as $tableauId => $values) {
            $tableauNode = $group->appendChild($dom->createElement('ValeursTableau'));
            $tableau = $tableauNode->appendChild($dom->createElement('tableau'));
            $this->appendTextElement($dom, $tableau, 'id', (string) $tableauId);

            $groupeValeurs = $tableauNode->appendChild($dom->createElement('groupeValeurs'));
            foreach ($values as $cell) {
                $valueNode = $groupeValeurs->appendChild($dom->createElement('ValeurCellule'));
                $cellNode = $valueNode->appendChild($dom->createElement('cellule'));
                $this->appendTextElement($dom, $cellNode, 'codeEdi', (string) $cell['code']);
                if ($cell['ligne'] !== null) {
                    $this->appendTextElement($dom, $valueNode, 'ligne', (string) $cell['ligne']);
                }
                $this->appendTextElement($dom, $valueNode, 'valeur', $cell['valeur']);
            }
        }
    }

    /**
     * @param array<string, mixed> $context
     * @return array<int, array{tableau:int, code:int, valeur:string, ligne:?int}>
     */
    private function buildMappedValues(array $context): array
    {
        $data = $this->indexedLiasseData($context['liasseData']);
        $calculated = $this->calculatedExporter->export((int) $context['user_id'], (int) $context['exercice']);
        $this->calculatedMappingMissing = $calculated['missing'];
        $values = $calculated['values'];

        $this->appendComputedPassageFiscalValues($values, $context, $data);

        foreach ($data as $tableauCode => $fields) {
            $tableauId = $this->tableauId($tableauCode);
            if ($tableauId === null) {
                continue;
            }

            foreach ($fields as $key => $value) {
                $cell = $this->directCellCode($tableauCode, $key);
                if ($cell !== null) {
                    $values[] = $this->mappedValue($tableauId, $cell, $value);
                    continue;
                }

                $dynamic = $this->dynamicCellCode($tableauCode, $key);
                if ($dynamic !== null) {
                    $values[] = $this->mappedValue($tableauId, $dynamic['code'], $value, $dynamic['line']);
                }
            }
        }

        return $this->deduplicateMappedValues($values);
    }

    /**
     * @param array<int, array{tableau:int, code:int, valeur:string, ligne:?int}> $values
     * @param array<string, mixed> $context
     * @param array<string, array<string, string>> $data
     */
    private function appendComputedPassageFiscalValues(array &$values, array $context, array $data): void
    {
        $tableauId = $this->tableauId('passage_fiscal');
        if ($tableauId === null) {
            return;
        }

        $resultatComptable = $this->resultatComptable($context['items']);
        $reintegrations = $this->numberFromData($data, 'passage_fiscal', 'reintegrations_courantes_total')
            + $this->numberFromData($data, 'passage_fiscal', 'reintegrations_non_courantes_total');
        $deductions = $this->numberFromData($data, 'passage_fiscal', 'deductions_courantes_total')
            + $this->numberFromData($data, 'passage_fiscal', 'deductions_non_courantes_total');

        foreach ([
            'benefice_net' => max(0.0, $resultatComptable),
            'perte_nette' => max(0.0, -$resultatComptable),
            'reintegrations_total' => $reintegrations,
            'deductions_total' => $deductions,
        ] as $key => $value) {
            $code = $this->directCellCode('passage_fiscal', $key);
            if ($code !== null) {
                $values[] = $this->mappedValue($tableauId, $code, $this->formatAmount($value));
            }
        }
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function indexedLiasseData(Collection $liasseData): array
    {
        $indexed = [];
        foreach ($liasseData as $field) {
            $indexed[(string) $field->tableau_code][(string) $field->cle] = (string) $field->valeur;
        }

        return $indexed;
    }

    private function tableauId(string $tableauCode): ?int
    {
        $id = config('edi.table_ids.'.$tableauCode);

        return is_numeric($id) ? (int) $id : null;
    }

    private function directCellCode(string $tableauCode, string $key): ?int
    {
        $code = config('edi.direct_cells.'.$tableauCode.'.'.$key);

        return is_numeric($code) ? (int) $code : null;
    }

    /**
     * @return array{code:int, line:int}|null
     */
    private function dynamicCellCode(string $tableauCode, string $key): ?array
    {
        if ($tableauCode === 'dotations_amortissements'
            && preg_match('/^r(\d+)_c(\d+)$/', $key, $matches) === 1) {
            $code = config('edi.dynamic_rows.dotations_amortissements.r.c'.$matches[2]);

            return is_numeric($code) ? ['code' => (int) $code, 'line' => ((int) $matches[1]) + 1] : null;
        }

        if ($tableauCode === 'repartition_capital'
            && preg_match('/^r(\d+)_c(\d+)$/', $key, $matches) === 1) {
            $code = config('edi.dynamic_rows.repartition_capital.r.c'.$matches[2]);

            return is_numeric($code) ? ['code' => (int) $code, 'line' => ((int) $matches[1]) + 1] : null;
        }

        if ($tableauCode === 'locations_baux'
            && preg_match('/^r(\d+)_c(\d+)$/', $key, $matches) === 1) {
            $code = config('edi.dynamic_rows.locations_baux.r.c'.$matches[2]);

            return is_numeric($code) ? ['code' => (int) $code, 'line' => ((int) $matches[1]) + 1] : null;
        }

        if ($tableauCode === 'passage_fiscal'
            && preg_match('/^(reintegration_courante|reintegration_non_courante|deduction_courante|deduction_non_courante)_(\d+)_(label|montant)$/', $key, $matches) === 1) {
            $code = config('edi.dynamic_rows.passage_fiscal.'.$matches[1].'.'.$matches[3]);

            return is_numeric($code) ? ['code' => (int) $code, 'line' => ((int) $matches[2]) + 1] : null;
        }

        return null;
    }

    /**
     * @return array{tableau:int, code:int, valeur:string, ligne:?int}
     */
    private function mappedValue(int $tableauId, int $code, mixed $value, ?int $line = null): array
    {
        return [
            'tableau' => $tableauId,
            'code' => $code,
            'valeur' => $this->formatValue($value),
            'ligne' => $line,
        ];
    }

    /**
     * @param array<int, array{tableau:int, code:int, valeur:string, ligne:?int}> $values
     * @return array<int, array{tableau:int, code:int, valeur:string, ligne:?int}>
     */
    private function deduplicateMappedValues(array $values): array
    {
        $unique = [];
        foreach ($values as $value) {
            if ($value['valeur'] === '') {
                continue;
            }
            $key = implode(':', [$value['tableau'], $value['code'], $value['ligne'] ?? 0]);
            $unique[$key] = $value;
        }

        return array_values($unique);
    }

    /**
     * Controle le contenu EDI avant serialisation XML.
     *
     * @param array<string, mixed> $context
     * @param array<int, array{tableau:int, code:int, valeur:string, ligne:?int}> $values
     * @return array<int, array<string, mixed>>
     */
    private function validateMappedValues(array $context, array $values): array
    {
        $errors = [];
        $errors = array_merge(
            $errors,
            $this->validateRequiredTableCoverage($values),
            $this->validateNoDuplicateCells($values),
            $this->validateCodesExistInCatalog($values),
            $this->validateCalculatedMappings(),
            $this->validateKnownLiasseValuesAreMapped($context, $values)
        );

        return $errors;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function validateCalculatedMappings(): array
    {
        if ($this->calculatedMappingMissing === []) {
            return [];
        }

        return [$this->ediError(
            'EDI_CALCULATED_MAPPING_MISSING',
            'Mappings EDI calcules incomplets',
            'Cellules calculees sans code EDI officiel retrouve : '.implode(', ', array_slice($this->calculatedMappingMissing, 0, 30)).'.',
            'Les tableaux calcules doivent etre exportes uniquement avec des codes presents dans ref_codes_edi.',
            'Completer ou corriger le catalogue de codification EDI, puis relancer la generation.'
        )];
    }

    /**
     * @param array<int, array{tableau:int, code:int, valeur:string, ligne:?int}> $values
     * @return array<int, array<string, mixed>>
     */
    private function validateRequiredTableCoverage(array $values): array
    {
        $required = (array) config('edi.required_complete_tables', []);
        if ($required === []) {
            return [];
        }

        $tableIds = (array) config('edi.table_ids', []);
        $exported = collect($values)->pluck('tableau')->map(fn ($id) => (int) $id)->unique()->all();
        $missing = [];

        foreach ($required as $tableauCode => $label) {
            $id = $tableIds[$tableauCode] ?? null;
            if (!is_numeric($id) || !in_array((int) $id, $exported, true)) {
                $missing[] = $label.' ('.$tableauCode.')';
            }
        }

        if ($missing === []) {
            return [];
        }

        return [$this->ediError(
            'EDI_INCOMPLETE_TABLE_COVERAGE',
            'Couverture EDI incomplete de la liasse',
            'Tableaux attendus absents du XML : '.implode(', ', array_slice($missing, 0, 30)).'.',
            'Le XML ne doit pas etre genere tant que tous les tableaux de la liasse applicative ne sont pas representes.',
            'Ajouter les projections EDI manquantes a partir de codes DGI verifies, ou retirer explicitement un tableau non teledeclarable avec justification.'
        )];
    }

    /**
     * @param array<int, array{tableau:int, code:int, valeur:string, ligne:?int}> $values
     * @return array<int, array<string, mixed>>
     */
    private function validateNoDuplicateCells(array $values): array
    {
        $seen = [];
        $duplicates = [];

        foreach ($values as $value) {
            $key = implode(':', [$value['tableau'], $value['code'], $value['ligne'] ?? 0]);
            if (isset($seen[$key])) {
                $duplicates[] = $key;
            }
            $seen[$key] = true;
        }

        if ($duplicates === []) {
            return [];
        }

        return [$this->ediError(
            'EDI_DUPLICATE_CELLS',
            'Cellules EDI dupliquees',
            'Des cellules XML ciblent plusieurs fois le meme tableau/code/ligne : '.implode(', ', array_slice($duplicates, 0, 10)).'.',
            'Chaque cellule DGI doit etre exportee une seule fois.',
            'Corriger le mapping EDI pour supprimer les doublons.'
        )];
    }

    /**
     * @param array<int, array{tableau:int, code:int, valeur:string, ligne:?int}> $values
     * @return array<int, array<string, mixed>>
     */
    private function validateCodesExistInCatalog(array $values): array
    {
        if (!Schema::hasTable('ref_codes_edi') || DB::table('ref_codes_edi')->count() === 0) {
            return [];
        }

        $codes = collect($values)->pluck('code')->map(fn ($code) => (string) $code)->unique()->values();
        $existing = DB::table('ref_codes_edi')
            ->whereIn('code_edi', $codes->all())
            ->pluck('code_edi')
            ->map(fn ($code) => (string) $code)
            ->all();
        $missing = $codes->diff($existing)->values()->all();

        if ($missing === []) {
            return [];
        }

        return [$this->ediError(
            'EDI_UNKNOWN_CODES',
            'Codes EDI absents du catalogue',
            'Codes utilises dans le XML mais absents de ref_codes_edi : '.implode(', ', array_slice($missing, 0, 20)).'.',
            'Chaque codeEdi exporte doit exister dans le catalogue DGI importe.',
            'Verifier config/edi.php ou reimporter le fichier de codification officiel.'
        )];
    }

    /**
     * @param array<string, mixed> $context
     * @param array<int, array{tableau:int, code:int, valeur:string, ligne:?int}> $values
     * @return array<int, array<string, mixed>>
     */
    private function validateKnownLiasseValuesAreMapped(array $context, array $values): array
    {
        $data = $this->indexedLiasseData($context['liasseData']);
        $mappedKeys = $this->mappedLiasseKeys($data);
        $unmapped = [];

        foreach ($data as $tableauCode => $fields) {
            if ($this->tableauId($tableauCode) === null) {
                $nonEmpty = array_filter($fields, fn ($value) => trim((string) $value) !== '');
                if ($nonEmpty !== []) {
                    $unmapped[] = $tableauCode.'.*';
                }
                continue;
            }

            foreach ($fields as $key => $value) {
                if (trim((string) $value) === '') {
                    continue;
                }

                if (!isset($mappedKeys[$tableauCode.'.'.$key])) {
                    $unmapped[] = $tableauCode.'.'.$key;
                }
            }
        }

        if ($unmapped === []) {
            return [];
        }

        return [$this->ediError(
            'EDI_UNMAPPED_LIASSE_FIELDS',
            'Rubriques de liasse non exportees',
            'Rubriques renseignees sans code EDI mappe : '.implode(', ', array_slice($unmapped, 0, 20)).'.',
            'Toute rubrique renseignee dans la liasse doit etre rattachee a une cellule DGI avant export.',
            'Completer config/edi.php pour ces rubriques ou confirmer que ces champs ne doivent pas etre teledeclares.'
        )];
    }

    /**
     * @param array<string, array<string, string>> $data
     * @return array<string, bool>
     */
    private function mappedLiasseKeys(array $data): array
    {
        $mapped = [
            'passage_fiscal.reintegrations_courantes_total' => true,
            'passage_fiscal.reintegrations_non_courantes_total' => true,
            'passage_fiscal.deductions_courantes_total' => true,
            'passage_fiscal.deductions_non_courantes_total' => true,
        ];

        foreach ((array) config('edi.non_edi_fields', []) as $field) {
            $mapped[(string) $field] = true;
        }

        foreach ($data as $tableauCode => $fields) {
            foreach (array_keys($fields) as $key) {
                if ($this->directCellCode($tableauCode, $key) !== null || $this->dynamicCellCode($tableauCode, $key) !== null) {
                    $mapped[$tableauCode.'.'.$key] = true;
                }
            }
        }

        return $mapped;
    }

    /**
     * @return array<string, mixed>
     */
    private function ediError(string $id, string $title, string $message, string $rule, string $suggestion): array
    {
        return [
            'id' => $id,
            'titre' => $title,
            'ok' => false,
            'ecart' => 1,
            'message' => $message,
            'bloquant' => true,
            'severity' => 'Erreur',
            'tableau' => 'EDI',
            'rubrique' => 'XML',
            'regle' => $rule,
            'suggestion' => $suggestion,
        ];
    }

    private function resultatComptable(Collection $items): float
    {
        $charges = $this->sumByPrefixes($items, ['6'], 'debiteur', 'crediteur');
        $produits = $this->sumByPrefixes($items, ['7'], 'crediteur', 'debiteur');

        return $produits - $charges;
    }

    private function sumByPrefixes(Collection $items, array $prefixes, string $positive, string $negative): float
    {
        return (float) $items
            ->filter(function ($item) use ($prefixes) {
                foreach ($prefixes as $prefix) {
                    if (str_starts_with((string) $item->compte, $prefix)) {
                        return true;
                    }
                }

                return false;
            })
            ->sum(fn ($item) => (float) $item->{'solde_'.$positive} - (float) $item->{'solde_'.$negative});
    }

    /**
     * @param array<string, array<string, string>> $data
     */
    private function numberFromData(array $data, string $tableauCode, string $key): float
    {
        return $this->numberValue($data[$tableauCode][$key] ?? 0);
    }

    private function numberValue(mixed $value): float
    {
        $normalized = str_replace(["\xc2\xa0", ' ', ','], ['', '', '.'], (string) $value);

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }

    private function formatValue(mixed $value): string
    {
        $value = trim((string) ($value ?? ''));
        $numeric = str_replace(["\xc2\xa0", ' ', ','], ['', '', '.'], $value);

        if ($value !== '' && is_numeric($numeric)) {
            return $this->formatAmount((float) $numeric);
        }

        return $value;
    }

    private function formatAmount(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    private function appendTextElement(DOMDocument $dom, DOMElement $parent, string $name, mixed $value): DOMElement
    {
        $element = $dom->createElement($name);
        $element->appendChild($dom->createTextNode((string) ($value ?? '')));
        $parent->appendChild($element);

        return $element;
    }
}
