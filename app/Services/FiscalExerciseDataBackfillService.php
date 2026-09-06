<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FiscalExerciseDataBackfillService
{
    private const TABLES = [
        'source_documents',
        'liasse_data',
        'liasse_field_sources',
        'liasse_table_validations',
    ];

    private const LOCK_ORDER = [
        'balance_items',
        'societes',
        'fiscal_exercises',
        ...self::TABLES,
    ];

    public function __construct(
        private readonly FiscalExerciseResolver $resolver,
    ) {
    }

    /**
     * Analyse toutes les lignes sans les modifier.
     *
     * @return array<string, array{total: int, already_linked: int, to_update: int}>
     */
    public function preflight(): array
    {
        return $this->buildPlan($this->loadRows(self::TABLES, false))['report'];
    }

    /**
     * Exécute le rattachement dans une transaction globale.
     *
     * @return array<string, array{total: int, already_linked: int, updated: int}>
     */
    public function run(): array
    {
        return DB::transaction(function (): array {
            $lockedRows = $this->loadRows(self::LOCK_ORDER, true);
            $before = $this->databaseSnapshot($lockedRows);
            $plan = $this->buildPlan($lockedRows);
            $updated = [];

            foreach (self::TABLES as $table) {
                $updated[$table] = $this->applyUpdates($table, $plan['updates'][$table]);
            }

            $this->validateAfterBackfill($before);

            return collect($plan['report'])
                ->mapWithKeys(fn (array $row, string $table): array => [
                    $table => [
                        'total' => $row['total'],
                        'already_linked' => $row['already_linked'],
                        'updated' => $updated[$table],
                    ],
                ])
                ->all();
        });
    }

    /**
     * @return array{
     *   report: array<string, array{total: int, already_linked: int, to_update: int}>,
     *   updates: array<string, array<int, array<string, int>>>
     * }
     */
    private function buildPlan(array $rowsByTable): array
    {
        $report = [];
        $updates = [];

        foreach (self::TABLES as $table) {
            $rows = $rowsByTable[$table];
            $tableUpdates = [];
            $alreadyLinked = 0;

            foreach ($rows as $row) {
                $expected = $table === 'liasse_data'
                    ? $this->resolveLiasseData($row)
                    : $this->resolveDirectFiscalLink($table, $row);

                if ($table === 'liasse_field_sources') {
                    $this->assertSourceDocumentConsistency($row);
                }

                $changes = [];

                if ($table === 'liasse_data') {
                    $changes = $this->expectedChange(
                        $table,
                        $row,
                        'societe_id',
                        $expected['societe_id'],
                        $changes,
                    );
                }

                $changes = $this->expectedChange(
                    $table,
                    $row,
                    'fiscal_exercise_id',
                    $expected['fiscal_exercise_id'],
                    $changes,
                );

                if ($changes === []) {
                    $alreadyLinked++;
                } else {
                    $tableUpdates[(int) $row->id] = $changes;
                }
            }

            $report[$table] = [
                'total' => $rows->count(),
                'already_linked' => $alreadyLinked,
                'to_update' => count($tableUpdates),
            ];
            $updates[$table] = $tableUpdates;
        }

        return compact('report', 'updates');
    }

    /**
     * @param array<int, string> $tables
     * @return array<string, Collection<int, object>>
     */
    private function loadRows(array $tables, bool $lockRows): array
    {
        $rows = [];

        foreach ($tables as $table) {
            $query = DB::table($table)->orderBy('id');

            if ($lockRows) {
                $query->lockForUpdate();
            }

            $rows[$table] = $query->get();
        }

        return $rows;
    }

    /** @return array{societe_id: int, fiscal_exercise_id: int} */
    private function resolveLiasseData(object $row): array
    {
        $societeIds = DB::table('societes')
            ->where('user_id', $row->user_id)
            ->orderBy('id')
            ->pluck('id');

        if ($societeIds->count() !== 1) {
            throw new RuntimeException(
                "liasse_data #{$row->id}: l'utilisateur {$row->user_id} doit posséder exactement une société."
            );
        }

        $societeId = (int) $societeIds->sole();
        $fiscalExercise = $this->resolver->resolve(
            (int) $row->user_id,
            (int) $row->exercice,
            $societeId,
        );

        return [
            'societe_id' => $societeId,
            'fiscal_exercise_id' => (int) $fiscalExercise->id,
        ];
    }

    /** @return array{fiscal_exercise_id: int} */
    private function resolveDirectFiscalLink(string $table, object $row): array
    {
        if ($row->societe_id === null) {
            throw new RuntimeException("{$table} #{$row->id}: société absente.");
        }

        $fiscalExercise = $this->resolver->resolve(
            (int) $row->user_id,
            (int) $row->exercice,
            (int) $row->societe_id,
        );

        return ['fiscal_exercise_id' => (int) $fiscalExercise->id];
    }

    private function assertSourceDocumentConsistency(object $row): void
    {
        if ($row->source_document_id === null) {
            return;
        }

        $document = DB::table('source_documents')->find($row->source_document_id);

        if ($document === null) {
            throw new RuntimeException(
                "liasse_field_sources #{$row->id}: document source {$row->source_document_id} introuvable."
            );
        }

        foreach (['user_id', 'societe_id', 'exercice'] as $column) {
            if ((int) $document->{$column} !== (int) $row->{$column}) {
                throw new RuntimeException(
                    "liasse_field_sources #{$row->id}: contexte incohérent avec le document source {$document->id}."
                );
            }
        }
    }

    /**
     * @param array<string, int> $changes
     * @return array<string, int>
     */
    private function expectedChange(
        string $table,
        object $row,
        string $column,
        int $expected,
        array $changes,
    ): array {
        $actual = $row->{$column};

        if ($actual === null) {
            $changes[$column] = $expected;

            return $changes;
        }

        if ((int) $actual !== $expected) {
            throw new RuntimeException(
                "{$table} #{$row->id}: {$column}={$actual} ne correspond pas à la valeur attendue {$expected}."
            );
        }

        return $changes;
    }

    /** @param array<int, array<string, int>> $updates */
    private function applyUpdates(string $table, array $updates): int
    {
        $updated = 0;

        foreach ($updates as $id => $changes) {
            $query = DB::table($table)->where('id', $id);

            foreach (array_keys($changes) as $column) {
                $query->whereNull($column);
            }

            if ($query->update($changes) !== 1) {
                throw new RuntimeException("{$table} #{$id}: rattachement concurrent ou ligne introuvable.");
            }

            $updated++;
        }

        return $updated;
    }

    /**
     * @param array<string, Collection<int, object>> $rowsByTable
     * @return array{counts: array<string, int>, balance_fingerprint: string}
     */
    private function databaseSnapshot(array $rowsByTable): array
    {
        return [
            'counts' => collect([
                'fiscal_exercises',
                'balance_items',
                ...self::TABLES,
            ])->mapWithKeys(fn (string $table): array => [$table => $rowsByTable[$table]->count()])
                ->all(),
            'balance_fingerprint' => $this->rowsFingerprint($rowsByTable['balance_items']),
        ];
    }

    /** @param array{counts: array<string, int>, balance_fingerprint: string} $before */
    private function validateAfterBackfill(array $before): void
    {
        $lockedRows = $this->loadRows(self::LOCK_ORDER, true);
        $after = $this->databaseSnapshot($lockedRows);

        if ($after !== $before) {
            throw new RuntimeException('Les compteurs métier, les exercices fiscaux ou les balances ont changé pendant le backfill.');
        }

        $this->buildPlan($lockedRows);

        foreach (self::TABLES as $table) {
            if (DB::table($table)->whereNull('fiscal_exercise_id')->exists()) {
                throw new RuntimeException("{$table}: au moins une ligne reste sans exercice fiscal.");
            }
        }

        if (DB::table('liasse_data')->whereNull('societe_id')->exists()) {
            throw new RuntimeException('liasse_data: au moins une ligne reste sans société.');
        }
    }

    /** @param Collection<int, object> $rows */
    private function rowsFingerprint(Collection $rows): string
    {
        $hash = hash_init('sha256');

        foreach ($rows as $row) {
            hash_update($hash, json_encode((array) $row, JSON_THROW_ON_ERROR));
        }

        return hash_final($hash);
    }
}
