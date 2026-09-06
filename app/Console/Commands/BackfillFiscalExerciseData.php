<?php

namespace App\Console\Commands;

use App\Services\FiscalExerciseDataBackfillService;
use Illuminate\Console\Command;
use Throwable;

class BackfillFiscalExerciseData extends Command
{
    protected $signature = 'fiscal-exercises:backfill-data {--force : Exécuter sans confirmation interactive}';

    protected $description = 'Rattache strictement les données fiscales historiques aux exercices fiscaux existants';

    public function handle(FiscalExerciseDataBackfillService $service): int
    {
        try {
            $preflight = $service->preflight();

            $this->info('Préflight terminé. Aucune donnée n’a encore été modifiée.');
            $this->renderReport($preflight, 'to_update');

            if (! $this->option('force') && ! $this->confirm('Exécuter le backfill transactionnel ?')) {
                $this->warn('Backfill annulé.');

                return self::INVALID;
            }

            $result = $service->run();

            $this->info('Backfill terminé avec succès.');
            $this->renderReport($result, 'updated');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Backfill interrompu : '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    /** @param array<string, array<string, int>> $report */
    private function renderReport(array $report, string $changedColumn): void
    {
        $this->table(
            ['Table', 'Total', 'Déjà correctes', $changedColumn === 'updated' ? 'Modifiées' : 'À rattacher'],
            collect($report)->map(
                fn (array $row, string $table): array => [
                    $table,
                    $row['total'],
                    $row['already_linked'],
                    $row[$changedColumn],
                ]
            )->values()->all(),
        );
    }
}
