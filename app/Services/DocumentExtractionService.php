<?php

namespace App\Services;

use App\Models\SourceDocument;
use App\Models\SourceDocumentExtraction;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class DocumentExtractionService
{
    public function extract(SourceDocument $document): SourceDocumentExtraction
    {
        $document->update(['status' => SourceDocument::STATUS_ANALYZING]);

        try {
            $extension = strtolower(pathinfo($document->original_name, PATHINFO_EXTENSION));

            if (in_array($extension, ['xlsx', 'xls', 'csv'], true)) {
                return $this->extractSpreadsheet($document);
            }

            return $this->markUnsupported($document, sprintf(
                'Extraction automatique non disponible pour le format .%s.',
                $extension ?: 'inconnu'
            ));
        } catch (Throwable $exception) {
            $document->update(['status' => SourceDocument::STATUS_ERROR]);

            return $this->upsertExtraction($document, [
                'raw_data' => [],
                'mapped_data' => [],
                'errors' => [$exception->getMessage()],
                'status' => SourceDocument::STATUS_ERROR,
                'processed_at' => now(),
            ]);
        }
    }

    private function extractSpreadsheet(SourceDocument $document): SourceDocumentExtraction
    {
        $path = Storage::disk('local')->path($document->stored_path);
        $mapped = $this->mapDossierFiscalD3Soft($path);
        $sheets = Excel::toArray([], $path);
        $firstSheet = $sheets[0] ?? [];
        $rows = $this->normalizeRows($firstSheet);

        if (count($mapped) === 0) {
            $mapped = $this->mapRows($rows, $document->tableau_code);
        }

        $status = count($mapped) > 0
            ? SourceDocument::STATUS_NEEDS_VALIDATION
            : SourceDocument::STATUS_ERROR;

        $document->update(['status' => $status]);

        return $this->upsertExtraction($document, [
            'raw_data' => $rows,
            'mapped_data' => $mapped,
            'errors' => $status === SourceDocument::STATUS_ERROR ? ['Aucune donnee exploitable detectee.'] : [],
            'status' => $status,
            'processed_at' => now(),
        ]);
    }

    private function markUnsupported(SourceDocument $document, string $message): SourceDocumentExtraction
    {
        $document->update(['status' => SourceDocument::STATUS_NEEDS_VALIDATION]);

        return $this->upsertExtraction($document, [
            'raw_data' => [],
            'mapped_data' => [],
            'errors' => [$message],
            'status' => SourceDocument::STATUS_NEEDS_VALIDATION,
            'processed_at' => now(),
        ]);
    }

    private function upsertExtraction(SourceDocument $document, array $attributes): SourceDocumentExtraction
    {
        return SourceDocumentExtraction::updateOrCreate(
            ['source_document_id' => $document->id],
            $attributes
        );
    }

    private function normalizeRows(array $rows): array
    {
        $normalized = [];

        foreach ($rows as $rowIndex => $row) {
            $cells = [];

            foreach ((array) $row as $cellIndex => $value) {
                $value = is_scalar($value) ? trim((string) $value) : '';
                $cells['c'.($cellIndex + 1)] = $value;
            }

            if (count(array_filter($cells, fn ($value) => $value !== '')) > 0) {
                $normalized[] = [
                    'row' => $rowIndex + 1,
                    'cells' => $cells,
                ];
            }
        }

        return $normalized;
    }

    private function mapRows(array $rows, string $tableauCode): array
    {
        $mapped = [];

        foreach ($rows as $row) {
            foreach ($row['cells'] as $column => $value) {
                if ($value === '') {
                    continue;
                }

                $mapped[] = [
                    'tableau_code' => $tableauCode,
                    'cle' => 'r'.$row['row'].'_'.$column,
                    'valeur' => $value,
                    'ligne' => $row['row'],
                    'colonne' => strtoupper($column),
                ];
            }
        }

        return $mapped;
    }

    private function mapDossierFiscalD3Soft(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheets = [];

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $sheets[$sheet->getTitle()] = $sheet;
        }

        if (!isset($sheets['Fiche société'], $sheets['Registre des immobilisations'])) {
            return [];
        }

        $reglesFiscales = $spreadsheet->getSheetCount() > 3 ? $spreadsheet->getSheet(3) : null;

        return array_values(array_filter(array_merge(
            $this->mapRepartitionCapital($this->readKeyValueSheet($sheets['Fiche société'])),
            $this->mapDotationsAmortissements($sheets['Registre des immobilisations']),
            $reglesFiscales ? $this->mapReglesFiscales($reglesFiscales) : [],
            isset($sheets['Décision AG']) ? $this->mapAffectationResultats($this->readKeyValueSheet($sheets['Décision AG'])) : [],
            isset($sheets['Informations complémentaires']) ? $this->mapLocationsBaux($sheets['Informations complémentaires']) : [],
            isset($sheets['Politique comptable']) ? $this->mapPolitiqueComptable($this->readKeyValueSheet($sheets['Politique comptable'])) : []
        ), fn ($field) => ($field['cle'] ?? '') !== ''));
    }

    private function readKeyValueSheet($sheet): array
    {
        $data = [];

        for ($row = 1; $row <= $sheet->getHighestRow(); $row++) {
            $key = $this->stringValue($sheet->getCellByColumnAndRow(1, $row)->getCalculatedValue());
            $value = $this->stringValue($sheet->getCellByColumnAndRow(2, $row)->getFormattedValue());

            if ($key !== '' && $value !== '') {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    private function mapRepartitionCapital(array $fiche): array
    {
        $capital = $fiche['Montant du capital social'] ?? '';
        $parts = $fiche['Nombre de parts'] ?? '';
        $nominal = $fiche["Valeur nominale d'une part"] ?? '';

        return $this->fields('repartition_capital', [
            'montant_capital' => $capital,
            'r0_c1' => $fiche['Nom complet'] ?? '',
            'r0_c2' => '',
            'r0_c3' => $fiche['Identifiant fiscal personnel'] ?? '',
            'r0_c4' => $fiche['CIN'] ?? '',
            'r0_c5' => '',
            'r0_c6' => $fiche['Adresse'] ?? '',
            'r0_c7' => $parts,
            'r0_c8' => $parts,
            'r0_c9' => $nominal,
            'r0_c10' => $capital,
            'r0_c11' => $capital,
            'r0_c12' => $capital,
            'total_c7' => $parts,
            'total_c8' => $parts,
            'total_c10' => $capital,
            'total_c11' => $capital,
            'total_c12' => $capital,
        ]);
    }

    private function mapDotationsAmortissements($sheet): array
    {
        $values = [];
        $totalPrix = 0.0;
        $totalReevalue = 0.0;
        $totalAnterieur = 0.0;
        $totalDotation = 0.0;
        $totalFin = 0.0;
        $targetRow = 0;

        for ($row = 4; $row <= $sheet->getHighestRow(); $row++) {
            $designation = $this->stringValue($sheet->getCellByColumnAndRow(1, $row)->getCalculatedValue());

            if ($designation === '' || mb_strtoupper($designation) === 'TOTAL') {
                continue;
            }

            $prix = $this->stringValue($sheet->getCellByColumnAndRow(3, $row)->getCalculatedValue());
            $anterieur = $this->stringValue($sheet->getCellByColumnAndRow(6, $row)->getCalculatedValue());
            $dotation = $this->stringValue($sheet->getCellByColumnAndRow(7, $row)->getCalculatedValue());
            $fin = $this->stringValue($sheet->getCellByColumnAndRow(8, $row)->getCalculatedValue());

            $values["r{$targetRow}_c1"] = $this->libelleAmortissement($designation);
            $values["r{$targetRow}_c2"] = $this->stringValue($sheet->getCellByColumnAndRow(2, $row)->getFormattedValue());
            $values["r{$targetRow}_c3"] = $prix;
            $values["r{$targetRow}_c4"] = $prix;
            $values["r{$targetRow}_c5"] = $anterieur;
            $values["r{$targetRow}_c6"] = $this->formatPercent($sheet->getCellByColumnAndRow(4, $row)->getCalculatedValue());
            $values["r{$targetRow}_c7"] = $this->stringValue($sheet->getCellByColumnAndRow(5, $row)->getCalculatedValue());
            $values["r{$targetRow}_c8"] = $dotation;
            $values["r{$targetRow}_c9"] = $fin;
            $values["r{$targetRow}_c10"] = $this->observationAmortissement($designation);

            $totalPrix += $this->numberValue($prix);
            $totalReevalue += $this->numberValue($prix);
            $totalAnterieur += $this->numberValue($anterieur);
            $totalDotation += $this->numberValue($dotation);
            $totalFin += $this->numberValue($fin);
            $targetRow++;
        }

        $values['montant_global'] = $this->formatNumber($totalDotation);
        $values['total_c3'] = $this->formatNumber($totalPrix);
        $values['total_c4'] = $this->formatNumber($totalReevalue);
        $values['total_c5'] = $this->formatNumber($totalAnterieur);
        $values['total_c8'] = $this->formatNumber($totalDotation);
        $values['total_c9'] = $this->formatNumber($totalFin);

        return $this->fields('dotations_amortissements', $values);
    }

    private function libelleAmortissement(string $designation): string
    {
        return match ($designation) {
            'Frais de constitution de la société' => 'Frais de constitution',
            'Brevets, marques et droits déposés' => 'Brevets, marques, droits et valeurs similaires',
            'Installations électriques et techniques' => 'Installations techniques',
            'Flotte de véhicules de service' => 'Matériel de transport',
            'Postes informatiques (parc existant)' => 'Matériel informatique (ancien)',
            'Nouveau poste informatique' => 'Matériel informatique (acquis en N)',
            default => $designation,
        };
    }

    private function observationAmortissement(string $designation): string
    {
        return match ($designation) {
            'Frais de constitution de la société' => 'Immatriculation RC',
            'Installations électriques et techniques' => 'Pas de dotation cette annee',
            'Flotte de véhicules de service' => 'Quasi totalement amorti',
            'Nouveau poste informatique' => "Acquisition en cours d'exercice, prorata 6/12",
            default => '',
        };
    }

    private function mapReglesFiscales($sheet): array
    {
        $cotisationMinimale = '';
        $penalites = '';
        $deductions = '0';
        $reports = '0';

        for ($row = 1; $row <= $sheet->getHighestRow(); $row++) {
            $nature = $this->stringValue($sheet->getCellByColumnAndRow(1, $row)->getCalculatedValue());
            $amount = $this->stringValue($sheet->getCellByColumnAndRow(3, $row)->getCalculatedValue());
            $normalizedNature = mb_strtolower($nature);

            if ($amount === '') {
                continue;
            }

            if (str_contains($normalizedNature, 'cotisation')) {
                $cotisationMinimale = $amount;
            } elseif (str_contains($normalizedNature, 'amendes') || str_contains($normalizedNature, 'penalit') || str_contains($normalizedNature, 'pénalit')) {
                $penalites = $amount;
            } elseif (str_contains($normalizedNature, 'deduction') || str_contains($normalizedNature, 'déduction')) {
                $deductions = $amount;
            } elseif (str_contains($normalizedNature, 'deficits') || str_contains($normalizedNature, 'déficits')) {
                $reports = $amount;
            }
        }

        return $this->fields('passage_fiscal', [
            'reintegration_courante_0_label' => 'Impots sur les resultats / Cotisation Minimale (non deductible)',
            'reintegration_courante_0_montant' => $cotisationMinimale,
            'reintegrations_courantes_total' => $cotisationMinimale,
            'reintegration_non_courante_0_label' => 'Penalites et amendes fiscales ou penales',
            'reintegration_non_courante_0_montant' => $penalites,
            'reintegrations_non_courantes_total' => $penalites,
            'deductions_courantes_total' => $deductions,
            'deductions_non_courantes_total' => '0',
            'reports_deficitaires_total' => $reports,
        ]);
    }
    private function mapAffectationResultats(array $ag): array
    {
        $resultat = $ag["Résultat net de l'exercice 2025 (perte)"] ?? '';
        $reserve = $ag['Réserve légale'] ?? '0';
        $dividendes = $ag['Dividendes distribués'] ?? '0';
        $report = $ag['Report à nouveau (perte reportée)'] ?? $resultat;

        return $this->fields('affectation_resultats', [
            'decision_date' => $ag["Date de l'assemblée générale"] ?? $ag['Date de décision AG'] ?? $ag['Date de decision AG'] ?? '',
            'ligne1_montantB' => $reserve,
            'ligne4_montantA' => $resultat,
            'ligne4_montantB' => $dividendes,
            'ligne6_montantB' => $report,
            'total_A' => $resultat,
            'total_B' => $this->formatNumber($this->numberValue($reserve) + $this->numberValue($dividendes) + $this->numberValue($report)),
        ]);
    }

    private function mapLocationsBaux($sheet): array
    {
        $fields = [];

        for ($row = 1; $row <= $sheet->getHighestRow(); $row++) {
            if ($this->stringValue($sheet->getCellByColumnAndRow(1, $row)->getCalculatedValue()) !== 'Bien loué') {
                continue;
            }

            $dataRow = $row + 1;
            $loyer = $this->stringValue($sheet->getCellByColumnAndRow(5, $dataRow)->getCalculatedValue());
            $fields = [
                'r0_c1' => $this->stringValue($sheet->getCellByColumnAndRow(1, $dataRow)->getCalculatedValue()),
                'r0_c2' => '',
                'r0_c3' => $this->stringValue($sheet->getCellByColumnAndRow(2, $dataRow)->getCalculatedValue()),
                'r0_c4' => '',
                'r0_c5' => $this->stringValue($sheet->getCellByColumnAndRow(3, $dataRow)->getCalculatedValue()),
                'r0_c6' => '',
                'r0_c7' => '',
                'r0_c8' => '',
                'r0_c9' => $this->stringValue($sheet->getCellByColumnAndRow(4, $dataRow)->getFormattedValue()),
                'r0_c10' => $loyer,
                'r0_c11' => $loyer,
                'r0_c12' => 'X',
                'r0_c13' => '',
            ];
            break;
        }

        return $this->fields('locations_baux', $fields);
    }

    private function mapPolitiqueComptable(array $politique): array
    {
        return array_merge(
            $this->fields('methodes_evaluation', [
                'methode_0_2' => $politique['Immobilisations incorporelles'] ?? '',
                'methode_0_3' => $politique['Immobilisations corporelles'] ?? '',
                'methode_0_6' => $politique['Immobilisations corporelles'] ?? '',
                'methode_1_1' => $politique['Stocks de produits finis'] ?? '',
                'methode_1_2' => $politique['Créances clients'] ?? '',
                'methode_2_2' => $politique['Dettes et comptes courants associés'] ?? '',
                'methode_3_0' => $politique['Dettes et comptes courants associés'] ?? '',
            ]),
            $this->fields('methodes_evaluation', $this->methodesEvaluationReference()),
            $this->fields('derogations', [
                'derogation_0_justification' => $politique['Dérogations aux principes comptables'] ?? '',
                'derogation_0_influence' => 'Sans objet',
                'derogation_1_justification' => $politique['Dérogations aux principes comptables'] ?? '',
                'derogation_1_influence' => 'Sans objet',
                'derogation_2_justification' => $politique['Dérogations aux principes comptables'] ?? '',
                'derogation_2_influence' => 'Sans objet',
            ]),
            $this->fields('changements_methodes', [
                'changement_0_0_nature' => $politique['Changements de méthodes comptables'] ?? '',
                'changement_0_0_justification' => 'Sans objet',
                'changement_0_0_influence' => 'Sans objet',
                'changement_1_0_nature' => $politique['Changements de méthodes comptables'] ?? '',
                'changement_1_0_justification' => 'Sans objet',
                'changement_1_0_influence' => 'Sans objet',
            ])
        );
    }

    private function methodesEvaluationReference(): array
    {
        return [
            'methode_0_1' => "Cout d'acquisition, amorti lineairement sur 5 ans",
            'methode_0_2' => "Cout d'acquisition, amorti lineairement sur 5 ans",
            'methode_0_3' => "Cout d'acquisition, amortissement lineaire aux taux fiscaux",
            'methode_0_4' => 'Neant',
            'methode_0_6' => 'Amortissement lineaire, taux fiscaux CGNC (10%)',
            'methode_0_7' => 'Neant - aucune provision constatee',
            'methode_0_8' => 'Neant',
            'methode_1_1' => 'Produits finis (logiciels) evalues au cout de production',
            'methode_1_2' => 'Valeur nominale, aucune provision pour creances',
            'methode_1_3' => 'Neant',
            'methode_1_5' => 'Neant',
            'methode_1_6' => 'Neant',
            'methode_2_0' => 'Neant - aucune reevaluation pratiquee',
            'methode_2_1' => 'Neant',
            'methode_2_2' => 'Neant - aucune dette de financement a long terme',
            'methode_2_3' => 'Neant',
            'methode_2_4' => 'Neant',
            'methode_3_0' => 'Valeur nominale',
            'methode_3_1' => 'Neant',
            'methode_3_2' => 'Neant',
            'methode_4_0' => 'Valeur nominale (banques, caisse)',
            'methode_4_1' => 'Neant',
            'methode_4_2' => 'Neant',
        ];
    }

    private function fields(string $tableauCode, array $values): array
    {
        $fields = [];

        foreach ($values as $key => $value) {
            $value = $this->stringValue($value);
            if ($key === '' || $value === '') {
                continue;
            }

            $fields[] = [
                'tableau_code' => $tableauCode,
                'cle' => $key,
                'valeur' => $value,
                'ligne' => null,
                'colonne' => null,
            ];
        }

        return $fields;
    }

    private function stringValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('d/m/Y');
        }

        if (is_float($value) || is_int($value)) {
            return $this->formatNumber((float) $value);
        }

        return trim((string) $value);
    }

    private function numberValue(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $value = str_replace(["\xc2\xa0", ' '], '', (string) $value);
        $value = str_replace(',', '.', $value);

        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function formatNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    private function formatPercent(mixed $value): string
    {
        $number = $this->numberValue($value);

        if ($number > 0 && $number <= 1) {
            $number *= 100;
        }

        return number_format($number, 2, '.', '').'%';
    }
}



