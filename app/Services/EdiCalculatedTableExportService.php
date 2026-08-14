<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Projection EDI des tableaux calcules dynamiquement.
 *
 * Cette classe ne calcule pas les montants metier : elle consomme
 * LiasseTableDataService, puis rattache chaque valeur au catalogue EDI importe.
 */
class EdiCalculatedTableExportService
{
    private array $catalog = [];
    private array $catalogByTable = [];

    public function __construct(private LiasseTableDataService $liasseTables)
    {
    }

    /**
     * @return array{values:array<int, array{tableau:int, code:int, valeur:string, ligne:?int}>, missing:array<int, string>}
     */
    public function export(int $userId, int $exercice): array
    {
        $this->loadCatalog();

        if ($this->catalog === []) {
            return [
                'values' => [],
                'missing' => [],
            ];
        }

        $values = [];
        $missing = [];

        $this->exportBilanActif($values, $missing, $userId, $exercice);
        $this->exportBilanPassif($values, $missing, $userId, $exercice);
        $this->exportCpc($values, $missing, $userId, $exercice);
        $this->exportEsg($values, $missing, $userId, $exercice);
        $this->exportDetailCpc($values, $missing, $userId, $exercice);
        $this->exportImmobilisations($values, $missing, $userId, $exercice);
        $this->exportAmortissements($values, $missing, $userId, $exercice);
        $this->exportProvisions($values, $missing, $userId, $exercice);
        $this->exportTva($values, $missing, $userId, $exercice);
        $this->exportDetailStocks($values, $missing, $userId, $exercice);
        $this->exportTableauFinancement($values, $missing, $userId, $exercice);

        return [
            'values' => $this->deduplicate($values),
            'missing' => array_values(array_unique($missing)),
        ];
    }

    private function exportBilanActif(array &$values, array &$missing, int $userId, int $exercice): void
    {
        $tableauId = $this->tableauId('bilan_actif');
        if ($tableauId === null) {
            $missing[] = 'bilan_actif : identifiant de tableau absent dans config/edi.php.';
            return;
        }

        $computed = $this->liasseTables->bilanActif($userId, $exercice);
        $rows = [];

        foreach ($computed['data'] as $section => $lines) {
            $rows[] = [$this->canonicalActifLabel($section), $this->sumActifRows($lines)];
            foreach ($lines as $label => $row) {
                $rows[] = [$this->canonicalActifLabel($label), $row];
            }

            if ($section === 'ECARTS DE CONVERSION - ACTIF ( e )') {
                $rows[] = ['TOTAL I (A+B+C+D+E)', $computed['totaux']['TOTAL_I']];
            }
            if ($section === 'ECART DE CONVERSION - ACTIF ( i ) (Elém. Circul.)') {
                $rows[] = ['TOTAL II (F+G+H+I)', $computed['totaux']['TOTAL_II']];
            }
            if ($section === 'TRESORERIE - ACTIF') {
                $rows[] = ['TOTAL III', $computed['totaux']['TOTAL_III']];
            }
        }

        $rows[] = ['TOTAL GENERAL I+II+III', (object) [
            'brut' => $computed['totaux']['TOTAL_I']->brut + $computed['totaux']['TOTAL_II']->brut + $computed['totaux']['TOTAL_III']->brut,
            'amort' => $computed['totaux']['TOTAL_I']->amort + $computed['totaux']['TOTAL_II']->amort + $computed['totaux']['TOTAL_III']->amort,
            'net' => $computed['totaux']['TOTAL_I']->net + $computed['totaux']['TOTAL_II']->net + $computed['totaux']['TOTAL_III']->net,
            'net_prec' => $computed['totaux']['TOTAL_I']->net_prec + $computed['totaux']['TOTAL_II']->net_prec + $computed['totaux']['TOTAL_III']->net_prec,
        ]];

        foreach ($rows as [$label, $row]) {
            $this->appendCatalogValue($values, $missing, $tableauId, $label, ['Brut exercice'], $row->brut ?? 0, ['Bilan (actif)']);
            $this->appendCatalogValue($values, $missing, $tableauId, $label, ['Amortissements et provisions : exercice'], $row->amort ?? 0, ['Bilan (actif)']);
            $this->appendCatalogValue($values, $missing, $tableauId, $label, ['Net exercice'], $row->net ?? 0, ['Bilan (actif)']);
            $this->appendCatalogValue($values, $missing, $tableauId, $label, ['Net exercice precedent', 'Net exercice précédent'], $row->net_prec ?? 0, ['Bilan (actif)']);
        }
    }

    private function exportBilanPassif(array &$values, array &$missing, int $userId, int $exercice): void
    {
        $tableauId = $this->tableauId('bilan_passif');
        if ($tableauId === null) {
            $missing[] = 'bilan_passif : identifiant de tableau absent dans config/edi.php.';
            return;
        }

        $computed = $this->liasseTables->bilanPassif($userId, $exercice);
        $rows = [];

        foreach ($computed['data'] as $section => $lines) {
            $rows[] = [$this->canonicalPassifLabel($section), $this->sumPassifRows($lines)];
            foreach ($lines as $label => $row) {
                $rows[] = [$this->canonicalPassifLabel($label), $row];
            }

            if ($section === 'ECARTS DE CONVERSION - PASSIF ( e )') {
                $rows[] = ['TOTAL I (A+B+C+D+E)', $computed['totaux']['TOTAL_I']];
            }
            if ($section === 'ECARTS DE CONVERSION - PASSIF ( h ) (Éléments Circulants)') {
                $rows[] = ['TOTAL II (F+G+H)', $computed['totaux']['TOTAL_II']];
            }
            if ($section === 'TRESORERIE PASSIF') {
                $rows[] = ['TOTAL III', $computed['totaux']['TOTAL_III']];
            }
        }

        $rows[] = ['TOTAL GENERAL I+II+III', (object) [
            'montant' => $computed['totaux']['TOTAL_I']->montant + $computed['totaux']['TOTAL_II']->montant + $computed['totaux']['TOTAL_III']->montant,
            'montant_prec' => $computed['totaux']['TOTAL_I']->montant_prec + $computed['totaux']['TOTAL_II']->montant_prec + $computed['totaux']['TOTAL_III']->montant_prec,
        ]];

        foreach ($rows as [$label, $row]) {
            $this->appendCatalogValue($values, $missing, $tableauId, $label, ['EXERCICE'], $row->montant ?? 0, ['Bilan (passif)']);
            $this->appendCatalogValue($values, $missing, $tableauId, $label, ['EXERCICE PRECEDENT', 'EXERCICE PRÉCÉDENT'], $row->montant_prec ?? 0, ['Bilan (passif)']);
        }

        foreach ([13402, 13403, 13413, 13414] as $code) {
            $values[] = [
                'tableau' => $tableauId,
                'code' => $code,
                'valeur' => '0.00',
                'ligne' => null,
            ];
        }
    }

    private function exportCpc(array &$values, array &$missing, int $userId, int $exercice): void
    {
        $tableauId = $this->tableauId('cpc');
        if ($tableauId === null) {
            $missing[] = 'cpc : identifiant de tableau absent dans config/edi.php.';
            return;
        }

        $computed = $this->liasseTables->cpc($userId, $exercice);
        foreach ($computed['cpcRows'] as $row) {
            $label = (string) $row['label'];
            $amounts = $row['values'];

            $this->appendCatalogValue($values, $missing, $tableauId, $label, ['Opérations propres à l\'exercice (1)', 'Propres à l\'exercice', 'Propres a l\'exercice'], $amounts->col1 ?? 0, ['COMPTE DE PRODUITS ET CHARGES', 'Bilan (passif)']);
            $this->appendCatalogValue($values, $missing, $tableauId, $label, ['Opérations concernant les exercices précédents (2)', 'Concernant les exercices précédents', 'Concernant les exercices precedents'], $amounts->col2 ?? 0, ['COMPTE DE PRODUITS ET CHARGES', 'Bilan (passif)']);
            $this->appendCatalogValue($values, $missing, $tableauId, $label, ['TOTAUX DE L\'EXERCICE (3 = 2+1)', 'Totaux de l\'exercice', 'TOTAL EXERCICE', 'EXERCICE'], $amounts->col3 ?? 0, ['COMPTE DE PRODUITS ET CHARGES', 'Bilan (passif)']);
            $this->appendCatalogValue($values, $missing, $tableauId, $label, ['TOTAUX DE L\'EXERCICE PRECEDENT (4)', 'Exercice précédent', 'EXERCICE PRECEDENT'], $amounts->col4 ?? 0, ['COMPTE DE PRODUITS ET CHARGES', 'Bilan (passif)']);
        }
    }

    private function exportEsg(array &$values, array &$missing, int $userId, int $exercice): void
    {
        $tableauId = $this->tableauId('esg');
        if ($tableauId === null) {
            $missing[] = 'esg : identifiant de tableau absent dans config/edi.php.';
            return;
        }

        $computed = $this->liasseTables->esg($userId, $exercice);
        foreach ($computed['rows'] as $row) {
            if (isset($row['section']) || !isset($row['k'])) {
                continue;
            }

            $label = $this->canonicalEsgLabel((string) $row['l']);
            $key = (string) $row['k'];
            $this->appendCatalogValue($values, $missing, $tableauId, $label, ['Exercice'], $computed['n'][$key] ?? 0, ['ETAT DES SOLDES DE GESTION (E.S.G)']);
            $this->appendCatalogValue($values, $missing, $tableauId, $label, ['Exercice Précédent', 'Exercice Precedent'], $computed['p'][$key] ?? 0, ['ETAT DES SOLDES DE GESTION (E.S.G)']);
        }
    }

    private function exportDetailCpc(array &$values, array &$missing, int $userId, int $exercice): void
    {
        $tableauId = $this->tableauId('detail_cpc');
        if ($tableauId === null) {
            $missing[] = 'detail_cpc : identifiant de tableau absent dans config/edi.php.';
            return;
        }

        $computed = $this->liasseTables->detailCpc($userId, $exercice);
        $rows = array_values(array_filter(
            $computed['rows'],
            fn (array $row) => !isset($row['section']) && !isset($row['poste'])
        ));

        $currentCodes = $this->detailCpcCodesByColumn('EXERCICE');
        $previousCodes = $this->detailCpcCodesByColumn('EXERCICE PRECEDENT');

        if (count($currentCodes) !== count($rows)) {
            $missing[] = 'detail_cpc : nombre de lignes exercice different du catalogue EDI ('.count($rows).' lignes liasse / '.count($currentCodes).' codes).';
        }

        if (count($previousCodes) !== count($rows)) {
            $missing[] = 'detail_cpc : nombre de lignes exercice precedent different du catalogue EDI ('.count($rows).' lignes liasse / '.count($previousCodes).' codes).';
        }

        foreach ($rows as $index => $row) {
            if (isset($currentCodes[$index])) {
                $values[] = [
                    'tableau' => $tableauId,
                    'code' => $currentCodes[$index],
                    'valeur' => number_format((float) ($row['n'] ?? 0), 2, '.', ''),
                    'ligne' => null,
                ];
            }

            if (isset($previousCodes[$index])) {
                $values[] = [
                    'tableau' => $tableauId,
                    'code' => $previousCodes[$index],
                    'valeur' => number_format((float) ($row['p'] ?? 0), 2, '.', ''),
                    'ligne' => null,
                ];
            }
        }
    }

    private function appendCatalogValue(array &$values, array &$missing, int $tableauId, string $label, array $columns, mixed $amount, array $catalogTables = []): void
    {
        $code = $this->findCode($label, $columns, $catalogTables);
        if ($code === null) {
            if (abs((float) $amount) < 0.005) {
                return;
            }

            $missing[] = $label.' / '.implode(' ou ', $columns);
            return;
        }

        $values[] = [
            'tableau' => $tableauId,
            'code' => $code,
            'valeur' => number_format((float) $amount, 2, '.', ''),
            'ligne' => null,
        ];
    }

    private function exportImmobilisations(array &$values, array &$missing, int $userId, int $exercice): void
    {
        $tableauId = $this->tableauId('immobilisations');
        if ($tableauId === null) {
            $missing[] = 'immobilisations : identifiant de tableau absent dans config/edi.php.';
            return;
        }

        $computed = $this->liasseTables->immobilisations($userId, $exercice);
        $table = ['TABLEAU DES IMMOBILISATIONS AUTRES QUE FINANCIERES'];
        $columns = [
            'debut' => ['MONTANT BRUT DEBUT EXERCICE'],
            'acquisition' => ['AUGMENTATION - Acquisition'],
            'production' => ["AUGMENTATION - Production par l'entreprise pour elle-même"],
            'virement_aug' => ['AUGMENTATION - Virement'],
            'cession' => ['DIMINUTION - Cession'],
            'retrait' => ['DIMINUTION - Retrait'],
            'virement_dim' => ['DIMINUTION - Virement'],
            'fin' => ['MONTANT BRUT FIN EXERCICE'],
        ];

        foreach ($computed['immoData'] as $section => $rows) {
            $this->appendRowColumns($values, $missing, $tableauId, $this->canonicalImmobilisationLabel($section), $computed['totauxImmo'][$section], $columns, $table);
            foreach ($rows as $label => $row) {
                $this->appendRowColumns($values, $missing, $tableauId, $this->canonicalImmobilisationLabel($label), $row, $columns, $table);
            }
        }

        $general = $this->sumRows($computed['totauxImmo'], array_keys($columns));
        $this->appendRowColumns($values, $missing, $tableauId, 'TOTAL GENERAL', $general, $columns, $table);
    }

    private function exportAmortissements(array &$values, array &$missing, int $userId, int $exercice): void
    {
        $tableauId = $this->tableauId('amortissements');
        if ($tableauId === null) {
            $missing[] = 'amortissements : identifiant de tableau absent dans config/edi.php.';
            return;
        }

        $computed = $this->liasseTables->amortissements($userId, $exercice);
        $table = ['TABLEAU DES AMORTISSEMENTS'];
        $columns = [
            'col1' => ['Cumul début exercice (1)'],
            'col2' => ["Dotation de l'exercice (2)"],
            'col3' => ['Amortissements sur immobilisations-sorties (3)'],
            'col4' => ["Cumul d'amortissement fin exercice (4 = 1+2-3)"],
        ];

        foreach ($computed['amortData'] as $section => $rows) {
            $this->appendRowColumns($values, $missing, $tableauId, $this->canonicalAmortissementLabel($section), $computed['totauxAmort'][$section], $columns, $table);
            foreach ($rows as $label => $row) {
                $this->appendRowColumns($values, $missing, $tableauId, $this->canonicalAmortissementLabel($label), $row, $columns, $table);
            }
        }

        $this->appendRowColumns($values, $missing, $tableauId, 'TOTAL GENERAL', $computed['totalGeneral'], $columns, $table);
    }

    private function exportProvisions(array &$values, array &$missing, int $userId, int $exercice): void
    {
        $tableauId = $this->tableauId('provisions');
        if ($tableauId === null) {
            $missing[] = 'provisions : identifiant de tableau absent dans config/edi.php.';
            return;
        }

        $computed = $this->liasseTables->provisions($userId, $exercice);
        $table = ['TABLEAU DES PROVISIONS'];
        $columns = [
            'col1' => ['Montant debut exercice'],
            'col2' => ["Dotations d'exploitation"],
            'col3' => ['Dotations financieres'],
            'col4' => ['Dotations non courantes'],
            'col5' => ["Reprises d'exploitation"],
            'col6' => ['Reprises financieres'],
            'col7' => ['Reprises non courantes'],
            'col8' => ['Montant fin exercice'],
        ];

        $empty = (object) ['col1' => 0.0, 'col2' => 0.0, 'col3' => 0.0, 'col4' => 0.0, 'col5' => 0.0, 'col6' => 0.0, 'col7' => 0.0];
        $depreciation = $computed['provisionsData']["PROVISIONS POUR DEPRECIATION DE L'ACTIF"] ?? [];
        $actifImmobilise = $this->sumRows(array_slice($depreciation, 0, 4, true), ['col1', 'col2', 'col3', 'col4', 'col5', 'col6', 'col7']);
        $actifCirculant = $this->sumRows(array_slice($depreciation, 4, 4, true), ['col1', 'col2', 'col3', 'col4', 'col5', 'col6', 'col7']);
        $tresorerie = $this->sumRows(array_slice($depreciation, 8, 1, true), ['col1', 'col2', 'col3', 'col4', 'col5', 'col6', 'col7']);
        $reglementees = clone $empty;
        $durables = $computed['totauxProvisions']['PROVISIONS DURABLES POUR RISQUES ET CHARGES'] ?? clone $empty;
        $autres = $computed['totauxProvisions']['AUTRES PROVISIONS POUR RISQUES ET CHARGES'] ?? clone $empty;
        $sousTotalA = $this->sumRows([$actifImmobilise, $reglementees], ['col1', 'col2', 'col3', 'col4', 'col5', 'col6', 'col7']);
        $sousTotalB = $this->sumRows([$durables, $actifCirculant, $autres, $tresorerie], ['col1', 'col2', 'col3', 'col4', 'col5', 'col6', 'col7']);
        $total = $this->sumRows([$sousTotalA, $sousTotalB], ['col1', 'col2', 'col3', 'col4', 'col5', 'col6', 'col7']);

        foreach ([
            "1. Provisions pour depreciation de l'actif immobilise" => $actifImmobilise,
            '2. Provisions reglementees' => $reglementees,
            'SOUS TOTAL (A)' => $sousTotalA,
            '3. Provisions durables pour risques et charges' => $durables,
            "4. Provisions pour depreciation de l'actif circulant (hors tresorerie)" => $actifCirculant,
            '5. Autres provisions pour risques et charge' => $autres,
            '6. Provisions pour depreciation des comptes de tresorerie' => $tresorerie,
            'SOUS TOTAL (B)' => $sousTotalB,
            'TOTAL (A+B)' => $total,
        ] as $label => $row) {
            $this->appendRowColumns($values, $missing, $tableauId, $label, $this->withProvisionEnding($row), $columns, $table);
        }
    }

    private function exportTva(array &$values, array &$missing, int $userId, int $exercice): void
    {
        $tableauId = $this->tableauId('tva');
        if ($tableauId === null) {
            $missing[] = 'tva : identifiant de tableau absent dans config/edi.php.';
            return;
        }

        $table = ['DETAIL DE LA TAXE SUR LA VALEUR AJOUTEE'];
        $columns = [
            'debut' => ["Solde au début de l'exercice"],
            'operations' => ["Opérations comptables de l'exercice (2)"],
            'declarations' => ["Déclarations T.V.A de l'exercice (3)"],
            'fin' => ["Solde fin d'exercice (1+2- 3=4)"],
        ];

        foreach ($this->liasseTables->tva($userId, $exercice)['tvaRows'] as $row) {
            $this->appendRowColumns($values, $missing, $tableauId, $this->canonicalTvaLabel((string) $row['label']), $row['values'], $columns, $table);
        }
    }

    private function exportDetailStocks(array &$values, array &$missing, int $userId, int $exercice): void
    {
        $tableauId = $this->tableauId('detail_stocks');
        if ($tableauId === null) {
            $missing[] = 'detail_stocks : identifiant de tableau absent dans config/edi.php.';
            return;
        }

        $computed = $this->liasseTables->detailStocks($userId, $exercice);
        $table = ['ETAT DETAILLE DES STOCKS'];
        $columns = [
            'final_brut' => ['STOCK FINAL : Montant brut (1)'],
            'final_provision' => ['STOCK FINAL : Provision pour dépréciation (2)'],
            'final_net' => ['STOCK FINAL : Montant net (3)'],
            'initial_brut' => ['STOCK INITIAL : Montant brut (4)'],
            'initial_provision' => ['STOCK INITIAL : Provision pour dépréciation (5)'],
            'initial_net' => ['STOCK INITIAL : Montant net (6)'],
            'variation' => ['Variation de stock en valeur (+ ou -) (7=6-3)'],
        ];

        foreach ($computed['stockSections'] as $section => $rows) {
            foreach ($rows as $row) {
                if (isset($row['label'])) {
                    $this->appendRowColumns($values, $missing, $tableauId, $this->canonicalStockLabel((string) $row['label']), $row['values'], $columns, $table);
                }
            }

            $this->appendRowColumns($values, $missing, $tableauId, $this->canonicalStockLabel('Total '.$section), $computed['stockTotals'][$section], $columns, $table);
        }

        $this->appendRowColumns($values, $missing, $tableauId, $this->canonicalStockLabel('TOTAL GENERAL'), $computed['stockTotalGeneral'], $columns, $table);
    }

    private function exportTableauFinancement(array &$values, array &$missing, int $userId, int $exercice): void
    {
        $tableauId = $this->tableauId('tableau_financement');
        if ($tableauId === null) {
            $missing[] = 'tableau_financement : identifiant de tableau absent dans config/edi.php.';
            return;
        }

        $computed = $this->liasseTables->tableauFinancement($userId, $exercice);
        $table = ['TABLEAU DE FINANCEMENT DE L\'EXERCICE'];

        foreach ($computed['synthese'] as $row) {
            $label = $this->canonicalFinancementLabel($this->stripHtmlLabel((string) $row['l']));
            $this->appendCatalogValue($values, $missing, $tableauId, $label, ['EXERCICE (a)'], $row['n'] ?? 0, $table);
            $this->appendCatalogValue($values, $missing, $tableauId, $label, ['EXERCICE PRECEDENT (b)'], $row['p'] ?? 0, $table);
            $this->appendCatalogValue($values, $missing, $tableauId, $label, ['VARIATION (a - b) EMPLOIS (C)'], $row['emploi'] ?? 0, $table);
            $this->appendCatalogValue($values, $missing, $tableauId, $label, ['VARIATION (a - b) RESSOURCES (D)'], $row['ressource'] ?? 0, $table);
        }

        foreach ($computed['fluxRows'] as $row) {
            if (isset($row['section'])) {
                continue;
            }

            $label = $this->canonicalFinancementLabel((string) $row['label']);
            $this->appendCatalogValue($values, $missing, $tableauId, $label, ['Exercice Emplois', 'Emplois Exercice'], $row['n_emploi'] ?? 0, $table);
            $this->appendCatalogValue($values, $missing, $tableauId, $label, ['Exercice Ressources', 'Ressources Exercice'], $row['n_ressource'] ?? 0, $table);
            $this->appendCatalogValue($values, $missing, $tableauId, $label, ['Exercice précédent Emplois', 'Emplois Exercice précédent'], $row['p_emploi'] ?? 0, $table);
            $this->appendCatalogValue($values, $missing, $tableauId, $label, ['Exercice précédent Ressources', 'Ressources Exercice précédent'], $row['p_ressource'] ?? 0, $table);
        }

        $this->appendRowColumns($values, $missing, $tableauId, 'TOTAL GENERAL', $computed['fluxTotal'], [
            'n_emploi' => ['EXERCICE EMPLOIS'],
            'n_ressource' => ['EXERCICE RESSOURCES'],
            'p_emploi' => ['EXERCICE PRECEDENT EMPLOIS'],
            'p_ressource' => ['EXERCICE PRECEDENT RESSOURCES'],
        ], $table);
    }

    private function appendRowColumns(array &$values, array &$missing, int $tableauId, string $label, object $row, array $columns, array $tables): void
    {
        foreach ($columns as $property => $columnLabels) {
            $this->appendCatalogValue($values, $missing, $tableauId, $label, $columnLabels, $row->{$property} ?? 0, $tables);
        }
    }

    private function withProvisionEnding(object $row): object
    {
        $row = clone $row;
        $row->col8 = (float) ($row->col1 ?? 0)
            + (float) ($row->col2 ?? 0)
            + (float) ($row->col3 ?? 0)
            + (float) ($row->col4 ?? 0)
            - (float) ($row->col5 ?? 0)
            - (float) ($row->col6 ?? 0)
            - (float) ($row->col7 ?? 0);

        return $row;
    }

    private function findCode(string $label, array $columns, array $catalogTables = []): ?int
    {
        $labelKey = $this->normalize($label);
        foreach ($columns as $column) {
            $columnKey = $this->normalize($column);
            foreach ($catalogTables as $table) {
                $tableKey = $this->normalize($table);
                if (isset($this->catalogByTable[$tableKey][$labelKey][$columnKey])) {
                    return $this->catalogByTable[$tableKey][$labelKey][$columnKey];
                }
            }

            if (isset($this->catalog[$labelKey][$columnKey])) {
                return $this->catalog[$labelKey][$columnKey];
            }

            $compactLabel = $this->compactKey($labelKey);
            $compactColumn = $this->compactKey($columnKey);
            foreach ($catalogTables as $table) {
                $tableKey = $this->normalize($table);
                foreach ($this->catalogByTable[$tableKey] ?? [] as $knownLabel => $knownColumns) {
                    if ($this->compactKey($knownLabel) !== $compactLabel) {
                        continue;
                    }
                    foreach ($knownColumns as $knownColumn => $code) {
                        if ($this->compactKey($knownColumn) === $compactColumn) {
                            return $code;
                        }
                    }
                }
            }

            foreach ($this->catalog as $knownLabel => $knownColumns) {
                if ($this->compactKey($knownLabel) !== $compactLabel) {
                    continue;
                }
                foreach ($knownColumns as $knownColumn => $code) {
                    if ($this->compactKey($knownColumn) === $compactColumn) {
                        return $code;
                    }
                }
            }
        }

        return null;
    }

    private function loadCatalog(): void
    {
        if ($this->catalog !== [] || !Schema::hasTable('ref_codes_edi')) {
            return;
        }

        DB::table('ref_codes_edi')
            ->whereNotNull('code_edi')
            ->whereNotNull('libelle')
            ->whereNotNull('col1')
            ->orderBy('id')
            ->get(['code_edi', 'tableau', 'libelle', 'col1', 'col3'])
            ->each(function ($row) {
                $tableName = (string) $row->tableau;
                $columnName = $this->catalogColumnName($tableName, (string) $row->col1, (string) $row->col3);
                $table = $this->normalize($tableName);
                $label = $this->normalize((string) $row->libelle);
                $column = $this->normalize($columnName);
                $code = (int) $row->code_edi;

                $this->catalog[$label][$column] ??= $code;
                $this->catalogByTable[$table][$label][$column] ??= $code;
            });
    }

    private function catalogColumnName(string $table, string $column, string $cell): string
    {
        $letters = preg_replace('/[^A-Z]/', '', strtoupper($cell)) ?? '';

        if (in_array($table, ['Bilan (actif)', 'Bilan (passif)'], true)
            && !str_contains($column, 'Type =')
            && !str_contains($column, '=Double')) {
            return $column;
        }

        if ($table === 'Bilan (actif)') {
            return [
                'W' => 'Brut exercice',
                'AH' => 'Amortissements et provisions : exercice',
                'BF' => 'Net exercice',
                'BO' => 'Net exercice précédent',
            ][$letters] ?? $column;
        }

        if ($table === 'Bilan (passif)') {
            return [
                'W' => 'EXERCICE',
                'AU' => 'EXERCICE PRECEDENT',
                'BL' => 'Propres à l\'exercice',
                'BQ' => 'Concernant les exercices précédents',
                'CC' => 'Totaux de l\'exercice',
                'CH' => 'Exercice précédent',
            ][$letters] ?? $column;
        }

        if ($table === 'TABLEAU DES IMMOBILISATIONS AUTRES QUE FINANCIERES') {
            return [
                'I' => 'MONTANT BRUT DEBUT EXERCICE',
                'J' => 'MONTANT BRUT DEBUT EXERCICE',
                'D' => 'MONTANT BRUT DEBUT EXERCICE',
                'Q' => 'AUGMENTATION - Acquisition',
                'R' => 'AUGMENTATION - Acquisition',
                'M' => 'AUGMENTATION - Acquisition',
                'AB' => "AUGMENTATION - Production par l'entreprise pour elle-même",
                'AC' => "AUGMENTATION - Production par l'entreprise pour elle-même",
                'Y' => "AUGMENTATION - Production par l'entreprise pour elle-même",
                'AN' => 'AUGMENTATION - Virement',
                'AK' => 'AUGMENTATION - Virement',
                'AY' => 'DIMINUTION - Cession',
                'AW' => 'DIMINUTION - Cession',
                'BG' => 'DIMINUTION - Retrait',
                'BE' => 'DIMINUTION - Retrait',
                'BN' => 'DIMINUTION - Virement',
                'BM' => 'DIMINUTION - Virement',
                'BS' => 'MONTANT BRUT FIN EXERCICE',
            ][$letters] ?? $column;
        }

        if ($table === 'TABLEAU DES AMORTISSEMENTS') {
            return [
                'W' => 'Cumul début exercice (1)',
                'AH' => "Dotation de l'exercice (2)",
                'AS' => 'Amortissements sur immobilisations-sorties (3)',
                'BK' => "Cumul d'amortissement fin exercice (4 = 1+2-3)",
            ][$letters] ?? $column;
        }

        if ($table === 'TABLEAU DES PROVISIONS') {
            return [
                'R' => 'Montant début exercice',
                'Y' => "Dotations d'exploitation",
                'AI' => 'Dotations financières',
                'AU' => 'Dotations non courantes',
                'BG' => "Reprises d'exploitation",
                'BQ' => 'Reprises financières',
                'CC' => 'Reprises non courantes',
                'CO' => 'Montant fin exercice',
            ][$letters] ?? $column;
        }

        if ($table === 'DETAIL DE LA TAXE SUR LA VALEUR AJOUTEE') {
            return [
                'W' => "Solde au début de l'exercice",
                'AK' => "Opérations comptables de l'exercice (2)",
                'BA' => "Déclarations T.V.A de l'exercice (3)",
                'BP' => "Solde fin d'exercice (1+2- 3=4)",
            ][$letters] ?? $column;
        }

        if ($table === 'TABLEAU DE FINANCEMENT DE L\'EXERCICE') {
            if (!str_contains($column, 'Type =') && !str_contains($column, '=Double')) {
                return $column;
            }

            return [
                'W' => 'EXERCICE EMPLOIS',
                'AI' => 'EXERCICE RESSOURCES',
                'AW' => 'EXERCICE PRECEDENT EMPLOIS',
                'BL' => 'EXERCICE PRECEDENT RESSOURCES',
            ][$letters] ?? $column;
        }

        if (!str_contains($column, 'Type =') && !str_contains($column, '=Double')) {
            return $column;
        }

        return $column;
    }

    /**
     * Les codes du detail CPC contiennent des lignes d'en-tete de poste sans
     * montant dans la vue. L'export retient uniquement les lignes valorisees.
     *
     * @return array<int, int>
     */
    private function detailCpcCodesByColumn(string $column): array
    {
        if (!Schema::hasTable('ref_codes_edi')) {
            return [];
        }

        return DB::table('ref_codes_edi')
            ->where('tableau', 'DETAIL DES POSTES DU C.P.C.')
            ->where('col1', $column)
            ->where('col2', 'Double')
            ->get(['code_edi', 'libelle', 'col3'])
            ->reject(fn ($row) => $this->isDetailCpcHeadingLabel((string) $row->libelle))
            ->sortBy(fn ($row) => $this->catalogCellRow((string) $row->col3))
            ->pluck('code_edi')
            ->map(fn ($code) => (int) $code)
            ->values()
            ->all();
    }

    private function isDetailCpcHeadingLabel(string $label): bool
    {
        return preg_match('/^(611|612|613\/614|617|618|638|658|711|712|713|718|719|738)\b/u', trim($label)) === 1;
    }

    private function catalogCellRow(string $cell): int
    {
        return (int) (preg_replace('/\D+/', '', $cell) ?: 0);
    }

    private function normalize(string $value): string
    {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = str_replace(['*', '.', ':', '-', '(', ')', '+', '/', '&'], ' ', $value);
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $value = $converted === false ? $value : $converted;
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    private function compactKey(string $value): string
    {
        return preg_replace('/\s+/', '', $value) ?? $value;
    }

    private function canonicalEsgLabel(string $label): string
    {
        $label = str_replace("\u{00a0}", ' ', $label);

        return [
            "1   Ventes de marchandises (en l'état )" => "1. Ventes de Marchandises ( en l'état)",
            '2   -  Achats revendus de marchandises' => '2. (-) Achats revendus de marchandises',
            "I   MARGES BRUTES SUR VENTES EN L'ETAT" => "I. (=) MARGE BRUTE SUR VENTES EN L'ETAT",
            "II   +  PRODUCTION DE L'EXERCICE (3+4+5)" => "II. (+) PRODUCTION DE L'EXERCICE (3+4+5)",
            '3   Ventes de biens et services produits' => '3. Ventes de biens et services produits',
            '4   Variation de stocks de produits' => '4. Variation stocks produits',
            "5   Immobilisations produites par l'entreprise pour elle même" => "5. Immobilisations produites par l'entreprise pour elle-même",
            "III   -  CONSOMMATION DE L'EXERCICE (6+7)" => "III. (-) CONSOMMATION DE L'EXERCICE (6+7)",
            '6   Achats consommés de matières et fournitures' => '6. Achats consommés de matières et fournitures',
            '7   Autres charges externes' => '7. Autres charges externes',
            'IV   VALEUR AJOUTEE ( I+II+III )' => 'IV. (=) VALEUR AJOUTEE (I+II+III)',
            "8   +  Subventions d'exploitation" => "8. (+) Subventions d'exploitation",
            "V   RESULTAT BRUT D'EXPLOITATION (E.B.E)" => "V. (=) EXCEDENT BRUT D'EXPLOITATION (EBE) OU INSUFFISANCE BRUTE D'EXPLOITATION (IBE)",
            '9   -  Impôts et taxes' => '9. (-) Impôts et taxes',
            '10   -  Charges de personnel' => '10. (-) Charges de personnel',
            "11   +  Autres produits d'exploitation" => "11. (+) Autres produits d'exploitation",
            "12   -  Autres charges d'exploitation" => "12. (-) Autres charges d'exploitation",
            "13   +  Reprises d'exploitation: transfert de charges" => "13. (+) Reprises d'exploitation, transferts de charges",
            "14   -  Dotations d'exploitation" => "14. (-) Dotations d'exploitation",
            'VI   RESULTAT D\'EXPLOITATION ( + ou - )' => 'VI. (=) RESULTAT D\'EXPLOITATION (+ou-)',
            'VII   RESULTAT FINANCIER' => 'VII. (+/-) RESULTAT FINANCIER',
            'VIII   RESULTAT COURANT ( + ou - )' => 'VIII. (=) RESULTAT COURANT (+ou- )',
            'IX   RESULTAT NON COURANT ( + ou - )' => 'IX. (+/-) RESULTAT NON COURANT',
            '15   -  Impôts sur les resultats' => '15. (-) IMPOTS SUR LES RESULTATS',
            "X   RESULTAT NET DE L'EXERCICE ( + ou - )" => "X. (=) RESULTAT NET DE L'EXERCICE",
            "1   RESULTAT NET DE L'EXERCICE ( + ou - )" => "1. Résultat net de l'exercice",
            '- Benefice (+)' => 'Bénéfice +',
            '- Perte   (-)' => 'Perte -',
            "2   +  Dotations d'exploitation" => "2. (+) Dotations d'exploitation (1)",
            '3   +  Dotations financières' => '3. (+) Dotations financières (1)',
            '4   +  Dotations non courantes' => '4. (+) Dotations non courantes (1)',
            "5   -  Reprises d'exploitation" => "5. (-) Reprises d'exploitation (2)",
            '6   -  Reprises financières' => '6. (-) Reprises financières (2)',
            '7   -  Reprises non courantes (2) (3)' => '7. (-) Reprises non courantes (2)(3)',
            '8   -  Produits des cession des immobilisations (1)' => "8. (-) Produits des cessions d'immobilisations (1)",
            '9   +  Valeurs nettes des immobilisations cédées' => '9. (+) Valeurs nettes d\'amortiss. des immo. cédées',
            'I   CAPACITE D\'AUTOFINANCEMENT ( C.A.F )' => 'I. CAPACITE D\'AUTOFINANCEMENT (C.A.F.)',
            '10   -  Distributions de bénéfices' => '10. (-) Distributions de bénéfices',
            'II   AUTOFINANCEMENT' => 'II. AUTOFINANCEMENT',
        ][$label] ?? $label;
    }

    private function canonicalActifLabel(string $label): string
    {
        return [
            'IMMOBILISATION EN NON VALEUR ( a )' => 'Immobilisations en non valeurs (A)',
            'IMMOBILISATIONS INCORPORELLES ( b )' => 'Immobilisations incorporelles (B)',
            'Autres immobilisations corporelles' => 'Autres immobilisations corporelles',
            'IMMOBILISATIONS CORPORELLES ( c )' => 'Immobilisations corporelles (C)',
            'Mobiliers, matériel de bureau et aménagements divers' => 'Mobilier, Matériel de bureau et aménagement divers',
            'IMMOBILISATIONS FINANCIERES ( d )' => 'Immobilisations financières (D)',
            'ECARTS DE CONVERSION - ACTIF ( e )' => 'Ecarts de conversion actif (E)',
            'Diminution des cadres immobilisées' => 'Diminution des créances immobilisées',
            'Augmentation des dettes de financement' => 'Augmentations des dettes de financement',
            'STOCKS ( f )' => 'Stocks (F)',
            'CREANCES DE L\'ACTIF CIRCULANT ( g )' => 'Créances de l\'actif circulant (G)',
            'Fournisseurs débiteurs, avances et acomptes' => 'Fournis. débiteurs, avances et',
            'Comptes d\'régularisation actif' => 'Comptes de régularisation- Actif',
            'TITRES ET VALEURS DE PLACEMENT ( h )' => 'Titres valeurs de placement (H)',
            'Titres et valeurs de placement' => 'Titres valeurs de placement (H)',
            'ECART DE CONVERSION - ACTIF ( i ) (Elém. Circul.)' => 'Ecarts de conversion actif (I) Eléments circulants',
            'Écarts de conversion - Actif (Éléments Circulants)' => 'Ecarts de conversion actif (I) Eléments circulants',
            'TRESORERIE - ACTIF' => 'Trésorerie-Actif',
            'Banques, T.G & CP' => 'Banques, T.G et C.C.P',
            'Caisses, régies d\'avances et accréditifs' => 'Caisse, Régie d\'avances et accréditifs',
        ][$label] ?? $label;
    }

    private function canonicalPassifLabel(string $label): string
    {
        return [
            'CAPITAUX PROPRES' => 'Total des capitaux propres (A)',
            'moins : Actionnaires, capital souscrit non appelé' => 'Moins : actionnaires, capital souscrit non appelé',
            'Écarts de réévaluation' => 'Ecarts de réévaluation',
            'Résultat net en instance d\'affectation (2)' => 'Résultats nets en instance d\'affectation (2)',
            'CAPITAUX PROPRES ASSIMILES ( b )' => 'Capitaux propres assimilés (B)',
            'Subventions d\'investissement' => 'Subvention d\'investissement',
            'DETTES DE FINANCEMENT ( c )' => 'Dettes de financement (C)',
            'PROVISIONS DURABLES POUR RISQUES ET CHARGES ( d )' => 'Provisions durables pour risques et charges (D)',
            'Provisions pour risks' => 'Provisions pour risques',
            'ECARTS DE CONVERSION - PASSIF ( e )' => 'Ecarts de conversion-passif (E)',
            'DETTES DU PASSIF CIRCULANT ( f )' => 'Dettes du passif circulant (F)',
            'Comptes de regularisation - passif' => 'Comptes de régularisation passif',
            'AUTRES PROVISIONS POUR RISQUES ET CHARGES ( g )' => 'Autres provisions pour risques et charges (G)',
            'Autres provisions pour risques et charges' => 'Autres provisions pour risques et charges (G)',
            'ECARTS DE CONVERSION - PASSIF ( h ) (Éléments Circulants)' => 'Ecarts de conversion - passif (Eléments circulants) (H)',
            'Écarts de conversion - Passif (Éléments Circulants)' => 'Ecarts de conversion - passif (Eléments circulants) (H)',
            'TRESORERIE PASSIF' => 'TOTAL III',
            'Banques ( soldes créditeurs )' => 'Banques (soldes créditeurs)',
        ][$label] ?? $label;
    }

    private function canonicalImmobilisationLabel(string $label): string
    {
        return [
            'IMMOBILISATIONS EN NON-VALEURS' => 'IMMOBILISATION EN NON-VALEURS',
            'Frais préliminaires' => 'Frais préliminaires',
            'Charges à répartir sur plusieurs exercices' => 'Charges à répartir sur plusieurs exercices',
            'Primes de remboursement des obligations' => 'Primes de remboursement obligations',
            'IMMOBILISATIONS INCORPORELLES' => 'IMMOBILISATIONS INCORPORELLES',
            'Immobilisations en recherche et développement' => 'Immobilisation en recherche et développement',
            'Brevets, marques, droits et valeurs similaires' => 'Brevets, marques, droits et valeurs similaires',
            'IMMOBILISATIONS CORPORELLES' => 'IMMOBILISATIONS CORPORELLES',
            'Installations techniques, matériel et outillage' => 'Installat. techniques, matériel et outillage',
            'Mobilier, matériel de bureau et aménagement' => 'Mobilier, matériel bureau et aménagements',
        ][$label] ?? $label;
    }

    private function canonicalAmortissementLabel(string $label): string
    {
        $label = ltrim($label, "- \t\n\r\0\x0B");

        return [
            'IMMOBILISATION EN NON-VALEURS' => 'IMMOBILISATION EN NON-VALEURS',
            'Primes de remboursement obligations' => 'Primes de remboursement des obligations',
            'IMMOBILISATIONS INCORPORELLES' => 'IMMOBILISATIONS INCORPORELLES',
            'Immobilisation en recherche et développement' => 'Immobilisation en recherche et développement',
            'Brevets, marques, droits et valeurs similaires' => 'Brevets, marques droits et valeurs similaires',
            'IMMOBILISATIONS CORPORELLES' => 'IMMOBILISATIONS CORPORELLES',
            'Mobilier, matériel de bureau et aménagement' => 'Mobilier, matériel de bureau et aménagements divers',
        ][$label] ?? $label;
    }

    private function canonicalProvisionLabel(string $label): string
    {
        $label = ltrim($label, "- \t\n\r\0\x0B");

        return [
            'PROVISIONS DURABLES POUR RISQUES ET CHARGES' => 'Provisions durables pour risques et charges',
            'AUTRES PROVISIONS POUR RISQUES ET CHARGES' => 'Autres provisions pour risques et charges',
            'PROVISIONS POUR DEPRECIATION DE L\'ACTIF' => "Provisions pour dépréciation de l'actif",
        ][$label] ?? $label;
    }

    private function canonicalTvaLabel(string $label): string
    {
        return [
            'A. T.V.A. Facturée' => 'A. T.V.A. Facturée',
            'B. T.V.A. Récupérable' => 'B. T.V.A. Récupérable',
            '- sur charges' => 'sur charges',
            '- sur immobilisations' => 'sur immobilisations',
            'C. T.V.A. due ou crédit de T.V.A = (A - B)' => 'C. T.V.A. due ou crédit de T.V.A = (A - B )',
        ][$label] ?? $label;
    }

    private function canonicalStockLabel(string $label): string
    {
        return [
            'Matières premières' => '3- Matières premières',
            'Matières consommables' => '4- Matières consommables',
            'Pièces détachées' => '5 - Pièces détachées',
            'Récupérables' => '7 * récupérables',
            'Vendus' => '8 * vendus',
            'Perdus' => '9 * perdus',
            'Total I. Stocks Approvisionnement' => '10- Total stocks approvisionnement',
            'Produits en cours' => '11- Produits en cours',
            'Études en cours' => '12- Etudes en cours',
            'Travaux en cours' => '13- Travaux en cours',
            'Services en cours' => '14- Services en cours',
            'Total II. Stocks En-cours Production de Biens et Services' => '15- Total Stocks des en cours',
            'Produits finis' => '16- Produits finis',
            'Biens finis' => '17- Biens finis',
            'Total III. Stocks Produits finis' => '18- Total Stocks Produits et Biens finis',
            'Déchets' => '19- Déchets',
            'Rebuts' => '20- Rebuts',
            'Matières de récupération' => '21- Matières de récupération',
            'Total IV. Stocks Produits Résiduels' => '22- Total Stocks Produits résiduels',
            'TOTAL GENERAL' => '23- TOTAL GENERAL (ligne 10+15+18+22)',
        ][$label] ?? $label;
    }

    private function canonicalFinancementLabel(string $label): string
    {
        $label = preg_replace('/\s+/', ' ', str_replace("\xc2\xa0", ' ', $label)) ?? $label;

        return [
            '1 Financement Permanent' => '1- Financement permanent',
            '2 Moins actif immobilisé' => '2- Moins actif immobilisé',
            '3 = Fonds de roulement fonctionnel (1-2) (A)' => '3- FONDS DE ROULEMENT FONCTIONNEL (1 - 2) (A)',
            '4 Actif circulant' => '4- Actif circulant',
            '5 Moins passif circulant' => '5- Moins passif circulant',
            '6 = Besoin de financement global (4-5) (B)' => '6- BESOIN DE FINANCEMENT GLOBAL (4 - 5) (B)',
            '7 TRESORERIE NETTE (Actif-Passif) = A-B' => '7- TRESORERIE NETTE (ACTIF - PASSIF) = A - B',
            'Autofinancement (A)' => '* AUTOFINANCEMENT (A)',
            "+ Capacité d'autofinancement" => "- Capacité d'autofinancement",
            '- Distributions de bénéfices' => '- Distribution de bénéfices',
            "Cessions et réductions d'immobilisations (B)" => "* CESSIONS ET REDUCTIONS D'IMMOBILISATIONS (B)",
            "+ Cessions d'immobilisations incorporelles" => "- Cessions d'immobilisations incorporelles",
            "+ Cessions d'immobilisations corporelles" => "- Cessions d'immobilisations corporelles",
            "+ Cessions d'immobilisations financières" => "- Cessions d'immobilisations",
            '+ Récupérations sur créances immobilisées' => '- Récupérations sur créances immobilisées',
            'Augmentation des capitaux propres et assimilés (C)' => '* AUGEMENTATION DES CAPITAUX PROPRES ET ASSIMILES (C)',
            '+ Augmentation du capital, apports' => '- Augmentations de capital, apports',
            "+ Subventions d'investissement" => "- Subventions d'investissement",
            'Augmentation des dettes de financement (D)' => '* AUGMENTATION DES DETTES DE FINANCEMENT (D) (nettes des primes de remboursements)',
            'TOTAL I - RESSOURCES STABLES' => 'TOTAL I- RESSOURCES STABLES (A+B+C+D)',
            "Acquisitions et augmentations d'immobilisations (E)" => "* ACQUISITIONS ET AUGEMENTATIONS D'IMMOBILISATIONS (E)",
            "Acquisitions d'immobilisations incorporelles" => "- Acquisitions d'immobilisations incorporelles",
            "Acquisitions d'immobilisations corporelles" => "- Acquisitions d'immobilisations corporelles",
            "Acquisitions d'immobilisations financières" => "- Acquisitions d'immobilisations financières",
            'Augmentation des créances immobilisées' => '- Augmentation des créances immobilisées',
            'Remboursement des capitaux propres (F)' => '* REMBOURSEMENT DES CAPITAUX PROPRES (F)',
            'Remboursements des dettes de financement (G)' => '* REMBOURSEMENT DES DETTES DE FINANCEMENT (G)',
            'Emplois en non-valeurs' => '* EMPLOIS EN NON VALEURS (H)',
            'TOTAL II - EMPLOIS STABLES' => 'TOTAL II- EMPLOIS STABLES (E+F+G+H)',
            'III- VARIATION DU BESOIN DE FINANCEMENT GLOBAL (B.F.G)' => 'III- VARIATION DU BESOIN DE FINANCEMENT GLOBAL (B.G.F)',
            'IV- VARIATION DE LA TRÉSORERIE' => 'IV- VARIATION DE LA TRESORERIE',
        ][$label] ?? $label;
    }

    private function stripHtmlLabel(string $label): string
    {
        $label = html_entity_decode(strip_tags($label), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/', ' ', $label) ?? $label);
    }

    private function sumRows(iterable $rows, array $columns): object
    {
        $total = array_fill_keys($columns, 0.0);
        foreach ($rows as $row) {
            foreach ($columns as $column) {
                $total[$column] += (float) ($row->{$column} ?? 0);
            }
        }

        return (object) $total;
    }

    private function sumActifRows(array $rows): object
    {
        return (object) [
            'brut' => collect($rows)->sum(fn ($row) => (float) $row->brut),
            'amort' => collect($rows)->sum(fn ($row) => (float) $row->amort),
            'net' => collect($rows)->sum(fn ($row) => (float) $row->net),
            'net_prec' => collect($rows)->sum(fn ($row) => (float) ($row->net_prec ?? 0)),
        ];
    }

    private function sumPassifRows(array $rows): object
    {
        return (object) [
            'montant' => collect($rows)->sum(fn ($row) => (float) $row->montant),
            'montant_prec' => collect($rows)->sum(fn ($row) => (float) ($row->montant_prec ?? 0)),
        ];
    }

    private function tableauId(string $code): ?int
    {
        $id = config('edi.table_ids.'.$code);

        return is_numeric($id) ? (int) $id : null;
    }

    private function deduplicate(array $values): array
    {
        $unique = [];
        foreach ($values as $value) {
            $key = implode(':', [$value['tableau'], $value['code'], $value['ligne'] ?? 0]);
            $unique[$key] = $value;
        }

        return array_values($unique);
    }
}

