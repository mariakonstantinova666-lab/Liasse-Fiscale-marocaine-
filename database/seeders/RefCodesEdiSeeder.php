<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class RefCodesEdiSeeder extends Seeder
{
    public function run(): void
    {
        $excelFile = collect([
            base_path('Codification des cellules modèle Normal.xlsx'),
            base_path('Codification des cellules modÃ¨le Normal.xlsx'),
        ])->first(fn (string $path) => File::exists($path));
        $csvFile = base_path('import_edi.csv');

        if ($excelFile !== null) {
            $count = $this->importFromExcel($excelFile);
            $this->command?->info("Succes : {$count} codes EDI importes depuis le fichier Excel de codification.");

            return;
        }

        if (File::exists($csvFile)) {
            $count = $this->importFromCsv($csvFile);
            $this->command?->warn("Succes : {$count} codes EDI importes depuis import_edi.csv. Attention : ce CSV peut perdre la ventilation par tableau.");

            return;
        }

        $this->command?->error('Aucun fichier de codification EDI trouve.');
    }

    private function importFromExcel(string $file): int
    {
        $reader = IOFactory::createReaderForFile($file);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $highestColumn = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        $currentTableau = (string) $sheet->getTitle();
        $columnHeaders = [];
        $count = 0;

        for ($row = 1; $row <= $highestRow; $row++) {
            $label = $this->clean($sheet->getCell([1, $row])->getValue());
            $rowCodes = [];

            for ($col = 1; $col <= $highestColumn; $col++) {
                $value = $this->clean($sheet->getCell([$col, $row])->getValue());
                $codeSource = $value;
                if (preg_match('/code\s*=/i', $value) === 1 && $row < $highestRow) {
                    $nextValue = $this->clean($sheet->getCell([$col, $row + 1])->getValue());
                    $codeSource = trim($value.' '.$nextValue);
                }

                if (preg_match('/code\s*=\s*(\d+)/i', $codeSource, $matches) === 1) {
                    $rowCodes[] = [
                        'code' => $matches[1],
                        'col' => $col,
                        'type' => $this->extractType($codeSource),
                    ];
                }
            }

            if ($rowCodes === []) {
                if ($this->isTableTitle($label)) {
                    $currentTableau = $label;
                    $columnHeaders = [];
                } elseif ($this->isTableTitleContinuation($currentTableau, $label)) {
                    $currentTableau .= ' '.$label;
                    $columnHeaders = [];
                }

                for ($col = 1; $col <= $highestColumn; $col++) {
                    $header = $this->clean($sheet->getCell([$col, $row])->getValue());
                    if ($this->isColumnHeader($header)) {
                        $columnHeaders[$col] = $header;
                    }
                }

                continue;
            }

            foreach ($rowCodes as $codeInfo) {
                $libelle = preg_match('/code\s*=/i', $label) === 1 || $label === ''
                    ? ($columnHeaders[$codeInfo['col']] ?? $label)
                    : $label;

                DB::table('ref_codes_edi')->updateOrInsert(
                    ['code_edi' => $codeInfo['code']],
                    [
                        'tableau' => $currentTableau,
                        'libelle' => $libelle,
                        'col1' => $columnHeaders[$codeInfo['col']] ?? '',
                        'col2' => $codeInfo['type'],
                        'col3' => Coordinate::stringFromColumnIndex($codeInfo['col']).$row,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
                $count++;
            }
        }

        return $count;
    }

    private function importFromCsv(string $file): int
    {
        $data = file($file);
        $count = 0;

        foreach ($data as $index => $line) {
            if ($index === 0) {
                continue;
            }

            $row = str_getcsv($line, ';');
            if (!isset($row[0]) || !is_numeric(trim($row[0]))) {
                continue;
            }

            DB::table('ref_codes_edi')->updateOrInsert(
                ['code_edi' => trim($row[0])],
                [
                    'tableau' => trim($row[1] ?? ''),
                    'libelle' => trim($row[2] ?? ''),
                    'col1' => trim($row[3] ?? ''),
                    'col2' => trim($row[4] ?? ''),
                    'col3' => trim($row[5] ?? ''),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            $count++;
        }

        return $count;
    }

    private function isTableTitle(string $label): bool
    {
        if ($label === '' || preg_match('/^\.+$/', $label) === 1) {
            return false;
        }

        if (preg_match('/^Bilan \(/i', $label) === 1) {
            return true;
        }

        if (mb_strtoupper($label) !== $label) {
            return false;
        }

        if (preg_match('/^(I|II|III|IV|V|VI|VII|VIII|IX|X)\./', $label) === 1) {
            return false;
        }

        if (preg_match('/^[A-Z]\./', $label) === 1) {
            return false;
        }

        return preg_match('/^(BILAN|COMPTE DE PRODUITS|PASSAGE DU|TABLEAU |ETAT |DETAIL |PRINCIPALES METHODES|OPERATIONS |CALCUL )/u', $label) === 1;
    }

    private function extractType(string $value): string
    {
        return preg_match('/Type\s*=\s*([A-Za-z]+)/i', $value, $matches) === 1 ? $matches[1] : '';
    }

    private function isColumnHeader(string $value): bool
    {
        if ($value === '' || str_contains(strtolower($value), 'code =')) {
            return false;
        }

        return preg_match('/^(\d+\)|\(Type|Type\s*=)/i', $value) !== 1;
    }

    private function isTableTitleContinuation(string $currentTableau, string $label): bool
    {
        if ($currentTableau === '' || $label === '' || mb_strtoupper($label) !== $label) {
            return false;
        }

        if ($this->isTableTitle($label)
            || preg_match('/^(I|II|III|IV|V|VI|VII|VIII|IX|X)\./', $label) === 1
            || preg_match('/^[A-Z]\./', $label) === 1) {
            return false;
        }

        return preg_match('/(QUE|AU|RELATIFS|CONTRACTES|LE|SPECIFIQUES A|COMPTABILISEES|RESULTAT|INTERVENUE|CESSIONS OU|FISCALE)$/u', $currentTableau) === 1;
    }

    private function clean(mixed $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string) ($value ?? '')) ?? '');
    }
}
