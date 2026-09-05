<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class FiscalExerciseBackfillService
{
    public function assertUnambiguous(): void
    {
        $ambiguities = $this->liasseDataAmbiguities();

        if ($ambiguities !== []) {
            throw new RuntimeException(
                'Backfill fiscal_exercises impossible : rattachement de liasse_data ambigu ('
                .implode('; ', $ambiguities).').'
            );
        }
    }

    /**
     * Create missing fiscal exercises without changing any existing business row.
     *
     * @return int Number of newly created fiscal exercises.
     */
    public function backfill(): int
    {
        $this->assertUnambiguous();

        $candidates = [];

        foreach (['balance_items', 'source_documents', 'liasse_field_sources', 'liasse_table_validations'] as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            DB::table($table)
                ->select(['user_id', 'societe_id', 'exercice'])
                ->distinct()
                ->orderBy('user_id')
                ->orderBy('societe_id')
                ->orderBy('exercice')
                ->get()
                ->each(function ($row) use (&$candidates): void {
                    $this->addCandidate(
                        $candidates,
                        (int) $row->user_id,
                        (int) $row->societe_id,
                        (int) $row->exercice
                    );
                });
        }

        if (Schema::hasTable('liasse_data')) {
            DB::table('liasse_data')
                ->select(['user_id', 'exercice'])
                ->distinct()
                ->orderBy('user_id')
                ->orderBy('exercice')
                ->get()
                ->each(function ($row) use (&$candidates): void {
                    $societeIds = DB::table('societes')
                        ->where('user_id', $row->user_id)
                        ->orderBy('id')
                        ->pluck('id');

                    $this->addCandidate(
                        $candidates,
                        (int) $row->user_id,
                        (int) $societeIds->first(),
                        (int) $row->exercice
                    );
                });
        }

        if ($candidates === []) {
            return 0;
        }

        return DB::transaction(function () use ($candidates): int {
            $before = DB::table('fiscal_exercises')->count();
            $now = now();
            $rows = array_map(fn (array $candidate): array => [
                ...$candidate,
                'created_at' => $now,
                'updated_at' => $now,
            ], array_values($candidates));

            DB::table('fiscal_exercises')->insertOrIgnore($rows);

            return DB::table('fiscal_exercises')->count() - $before;
        });
    }

    /** @param array<string, array{user_id:int, societe_id:int, exercice:int}> $candidates */
    private function addCandidate(array &$candidates, int $userId, int $societeId, int $exercice): void
    {
        $key = $societeId.'|'.$exercice;
        $candidates[$key] = [
            'user_id' => $userId,
            'societe_id' => $societeId,
            'exercice' => $exercice,
        ];
    }

    /** @return string[] */
    private function liasseDataAmbiguities(): array
    {
        if (!Schema::hasTable('liasse_data')) {
            return [];
        }

        $ambiguities = [];
        DB::table('liasse_data')
            ->select(['user_id', 'exercice'])
            ->distinct()
            ->orderBy('user_id')
            ->orderBy('exercice')
            ->get()
            ->each(function ($row) use (&$ambiguities): void {
                $societeCount = DB::table('societes')->where('user_id', $row->user_id)->count();

                if ($societeCount !== 1) {
                    $ambiguities[] = sprintf(
                        'user_id=%d, exercice=%d, societes=%d',
                        $row->user_id,
                        $row->exercice,
                        $societeCount
                    );
                }
            });

        return $ambiguities;
    }
}
