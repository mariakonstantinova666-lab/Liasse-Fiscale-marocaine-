<?php

namespace Tests\Feature;

use App\Models\FiscalExercise;
use App\Models\Societe;
use App\Models\User;
use App\Services\FiscalExerciseDataBackfillService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Tests\TestCase;

class FiscalExerciseDataBackfillTest extends TestCase
{
    use RefreshDatabase;

    public function test_nominal_backfill_links_every_supported_table(): void
    {
        $context = $this->completeContext();

        $result = $this->service()->run();

        $this->assertSame(1, $result['source_documents']['updated']);
        $this->assertSame(1, $result['liasse_data']['updated']);
        $this->assertSame(1, $result['liasse_field_sources']['updated']);
        $this->assertSame(1, $result['liasse_table_validations']['updated']);
        $this->assertDatabaseHas('source_documents', ['id' => $context['document_id'], 'fiscal_exercise_id' => $context['exercise']->id]);
        $this->assertDatabaseHas('liasse_data', ['id' => $context['liasse_id'], 'societe_id' => $context['societe']->id, 'fiscal_exercise_id' => $context['exercise']->id]);
        $this->assertDatabaseHas('liasse_field_sources', ['id' => $context['field_id'], 'fiscal_exercise_id' => $context['exercise']->id]);
        $this->assertDatabaseHas('liasse_table_validations', ['id' => $context['validation_id'], 'fiscal_exercise_id' => $context['exercise']->id]);
    }

    public function test_backfill_is_idempotent(): void
    {
        $this->completeContext();
        $this->service()->run();
        $before = $this->fiscalRows();

        $result = $this->service()->run();

        $this->assertSame($before, $this->fiscalRows());
        foreach ($result as $table) {
            $this->assertSame(0, $table['updated']);
            $this->assertSame($table['total'], $table['already_linked']);
        }
    }

    public function test_liasse_data_user_without_societe_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->insertLiasseData($user->id, 2026);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('exactement une société');

        $this->service()->run();
    }

    public function test_liasse_data_user_with_multiple_societes_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->societe($user, 'Société A');
        $this->societe($user, 'Société B');
        $liasseId = $this->insertLiasseData($user->id, 2026);

        try {
            $this->service()->run();
            $this->fail('Le backfill devait refuser plusieurs sociétés.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('exactement une société', $exception->getMessage());
        }

        $this->assertDatabaseHas('liasse_data', ['id' => $liasseId, 'societe_id' => null, 'fiscal_exercise_id' => null]);
    }

    public function test_missing_fiscal_exercise_is_rejected_without_creating_one(): void
    {
        [$user, $societe] = $this->userAndSociete();
        $documentId = $this->insertDocument($user->id, $societe->id, 2026);

        try {
            $this->service()->run();
            $this->fail('Le backfill devait refuser un exercice fiscal absent.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Aucun exercice fiscal 2026', $exception->getMessage());
        }

        $this->assertDatabaseCount('fiscal_exercises', 0);
        $this->assertDatabaseHas('source_documents', ['id' => $documentId, 'fiscal_exercise_id' => null]);
    }

    public function test_societe_owned_by_another_user_is_rejected(): void
    {
        $user = User::factory()->create();
        [$owner, $societe] = $this->userAndSociete();
        $this->exercise($owner, $societe, 2026);
        $documentId = $this->insertDocument($user->id, $societe->id, 2026);

        try {
            $this->service()->run();
            $this->fail('Le backfill devait refuser la société d’un autre utilisateur.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('n’appartient pas', $exception->getMessage());
        }

        $this->assertDatabaseHas('source_documents', ['id' => $documentId, 'fiscal_exercise_id' => null]);
    }

    public function test_coherent_non_null_links_are_kept_unchanged(): void
    {
        $context = $this->completeContext(linked: true);
        $before = $this->fiscalRows();

        $result = $this->service()->run();

        $this->assertSame($before, $this->fiscalRows());
        foreach ($result as $table) {
            $this->assertSame(0, $table['updated']);
        }
        $this->assertDatabaseHas('liasse_data', ['id' => $context['liasse_id'], 'societe_id' => $context['societe']->id]);
    }

    public function test_incoherent_non_null_link_causes_global_rejection(): void
    {
        $context = $this->completeContext();
        $wrongExercise = $this->exercise($context['user'], $context['societe'], 2025);
        DB::table('source_documents')->where('id', $context['document_id'])->update([
            'fiscal_exercise_id' => $wrongExercise->id,
        ]);

        try {
            $this->service()->run();
            $this->fail('Le backfill devait refuser un rattachement incohérent.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('ne correspond pas', $exception->getMessage());
        }

        $this->assertDatabaseHas('liasse_data', ['id' => $context['liasse_id'], 'societe_id' => null, 'fiscal_exercise_id' => null]);
        $this->assertDatabaseHas('source_documents', ['id' => $context['document_id'], 'fiscal_exercise_id' => $wrongExercise->id]);
    }

    public function test_database_failure_after_first_table_rolls_back_every_update(): void
    {
        $context = $this->completeContext();
        DB::statement("CREATE TRIGGER reject_liasse_backfill BEFORE UPDATE OF fiscal_exercise_id ON liasse_data BEGIN SELECT RAISE(ABORT, 'forced backfill failure'); END");

        try {
            $this->service()->run();
            $this->fail('Une erreur SQL forcée devait interrompre le backfill.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('forced backfill failure', $exception->getMessage());
        }

        $this->assertDatabaseHas('source_documents', ['id' => $context['document_id'], 'fiscal_exercise_id' => null]);
        $this->assertDatabaseHas('liasse_data', ['id' => $context['liasse_id'], 'societe_id' => null, 'fiscal_exercise_id' => null]);
        $this->assertDatabaseHas('liasse_field_sources', ['id' => $context['field_id'], 'fiscal_exercise_id' => null]);
    }

    public function test_field_source_must_match_its_source_document_context(): void
    {
        [$user, $societe] = $this->userAndSociete();
        $this->exercise($user, $societe, 2025);
        $this->exercise($user, $societe, 2026);
        $documentId = $this->insertDocument($user->id, $societe->id, 2025);
        $fieldId = $this->insertFieldSource($user->id, $societe->id, 2026, $documentId);

        try {
            $this->service()->run();
            $this->fail('La provenance incohérente devait être refusée.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('contexte incohérent', $exception->getMessage());
        }

        $this->assertDatabaseHas('source_documents', ['id' => $documentId, 'fiscal_exercise_id' => null]);
        $this->assertDatabaseHas('liasse_field_sources', ['id' => $fieldId, 'fiscal_exercise_id' => null]);
    }

    public function test_balance_items_are_byte_for_byte_unchanged(): void
    {
        $context = $this->completeContext();
        DB::table('balance_items')->insert([
            'user_id' => $context['user']->id,
            'societe_id' => $context['societe']->id,
            'exercice' => 2026,
            'compte' => '7111',
            'libelle' => 'Ventes',
            'solde_debiteur' => 0,
            'solde_crediteur' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $before = DB::table('balance_items')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();

        $this->service()->run();

        $this->assertSame($before, DB::table('balance_items')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all());
    }

    public function test_fiscal_exercises_are_neither_created_nor_deleted(): void
    {
        $this->completeContext();
        $before = DB::table('fiscal_exercises')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();

        $this->service()->run();

        $this->assertSame($before, DB::table('fiscal_exercises')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all());
    }

    public function test_balance_fingerprint_failure_rolls_back_every_change(): void
    {
        $context = $this->completeContext();
        DB::table('balance_items')->insert([
            'user_id' => $context['user']->id,
            'societe_id' => $context['societe']->id,
            'exercice' => 2026,
            'compte' => '7111',
            'libelle' => 'Ventes',
            'solde_debiteur' => 0,
            'solde_crediteur' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::statement("CREATE TRIGGER alter_balance_during_backfill AFTER UPDATE OF fiscal_exercise_id ON source_documents BEGIN UPDATE balance_items SET libelle = 'Altérée' WHERE compte = '7111'; END");

        try {
            $this->service()->run();
            $this->fail('L’altération forcée de la balance devait interrompre le backfill.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('balances ont changé', $exception->getMessage());
        }

        $this->assertDatabaseHas('balance_items', ['compte' => '7111', 'libelle' => 'Ventes']);
        $this->assertDatabaseHas('source_documents', ['id' => $context['document_id'], 'fiscal_exercise_id' => null]);
        $this->assertDatabaseHas('liasse_data', ['id' => $context['liasse_id'], 'societe_id' => null, 'fiscal_exercise_id' => null]);
    }

    public function test_empty_table_validations_is_supported(): void
    {
        [$user, $societe] = $this->userAndSociete();
        $this->exercise($user, $societe, 2026);
        $this->insertLiasseData($user->id, 2026);

        $result = $this->service()->run();

        $this->assertSame(['total' => 0, 'already_linked' => 0, 'updated' => 0], $result['liasse_table_validations']);
        $this->assertDatabaseCount('liasse_table_validations', 0);
    }

    public function test_preflight_reports_changes_without_writing(): void
    {
        $context = $this->completeContext();
        $before = $this->fiscalRows();

        $report = $this->service()->preflight();

        $this->assertSame(1, $report['source_documents']['to_update']);
        $this->assertSame(1, $report['liasse_data']['to_update']);
        $this->assertSame(1, $report['liasse_field_sources']['to_update']);
        $this->assertSame(1, $report['liasse_table_validations']['to_update']);
        $this->assertSame($before, $this->fiscalRows());
        $this->assertDatabaseHas('source_documents', ['id' => $context['document_id'], 'fiscal_exercise_id' => null]);
    }

    public function test_command_reports_success_and_counters(): void
    {
        $this->completeContext();

        $this->artisan('fiscal-exercises:backfill-data', ['--force' => true])
            ->expectsOutputToContain('Préflight terminé')
            ->expectsOutputToContain('Backfill terminé avec succès')
            ->assertSuccessful();
    }

    public function test_command_returns_failure_and_clear_error(): void
    {
        $context = $this->completeContext();
        $wrongExercise = $this->exercise($context['user'], $context['societe'], 2025);
        DB::table('source_documents')->where('id', $context['document_id'])->update([
            'fiscal_exercise_id' => $wrongExercise->id,
        ]);

        $exitCode = Artisan::call('fiscal-exercises:backfill-data', ['--force' => true]);
        $output = Artisan::output();

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('Backfill interrompu', $output);
        $this->assertStringContainsString('fiscal_exercise_id=', $output);
        $this->assertStringContainsString('ne correspond pas', $output);
    }

    private function service(): FiscalExerciseDataBackfillService
    {
        return app(FiscalExerciseDataBackfillService::class);
    }

    /** @return array<string, mixed> */
    private function completeContext(bool $linked = false): array
    {
        [$user, $societe] = $this->userAndSociete();
        $exercise = $this->exercise($user, $societe, 2026);
        $fiscalId = $linked ? $exercise->id : null;
        $documentId = $this->insertDocument($user->id, $societe->id, 2026, $fiscalId);
        $liasseId = $this->insertLiasseData($user->id, 2026, $linked ? $societe->id : null, $fiscalId);
        $fieldId = $this->insertFieldSource($user->id, $societe->id, 2026, $documentId, $fiscalId);
        $validationId = $this->insertValidation($user->id, $societe->id, 2026, $fiscalId);

        return compact('user', 'societe', 'exercise', 'documentId', 'liasseId', 'fieldId', 'validationId') + [
            'document_id' => $documentId,
            'liasse_id' => $liasseId,
            'field_id' => $fieldId,
            'validation_id' => $validationId,
        ];
    }

    /** @return array{User, Societe} */
    private function userAndSociete(): array
    {
        $user = User::factory()->create();

        return [$user, $this->societe($user, 'Société test')];
    }

    private function societe(User $user, string $name): Societe
    {
        return Societe::create(['user_id' => $user->id, 'nom_societe' => $name]);
    }

    private function exercise(User $user, Societe $societe, int $year): FiscalExercise
    {
        return FiscalExercise::create(['user_id' => $user->id, 'societe_id' => $societe->id, 'exercice' => $year]);
    }

    private function insertLiasseData(int $userId, int $year, ?int $societeId = null, ?int $fiscalId = null): int
    {
        return DB::table('liasse_data')->insertGetId([
            'user_id' => $userId, 'societe_id' => $societeId, 'fiscal_exercise_id' => $fiscalId,
            'exercice' => $year, 'tableau_code' => 'passage_fiscal', 'cle' => 'test', 'valeur' => '1',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function insertDocument(int $userId, int $societeId, int $year, ?int $fiscalId = null): int
    {
        return DB::table('source_documents')->insertGetId([
            'user_id' => $userId, 'societe_id' => $societeId, 'fiscal_exercise_id' => $fiscalId,
            'exercice' => $year, 'document_type' => 'autre', 'tableau_code' => 'autre',
            'original_name' => 'test.txt', 'stored_path' => 'source-documents/test.txt',
            'size' => 0, 'status' => 'imported', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function insertFieldSource(int $userId, int $societeId, int $year, int $documentId, ?int $fiscalId = null): int
    {
        return DB::table('liasse_field_sources')->insertGetId([
            'user_id' => $userId, 'societe_id' => $societeId, 'fiscal_exercise_id' => $fiscalId,
            'source_document_id' => $documentId, 'exercice' => $year, 'tableau_code' => 'passage_fiscal',
            'cle' => 'test', 'valeur' => '1', 'source_type' => 'document', 'status' => 'draft',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function insertValidation(int $userId, int $societeId, int $year, ?int $fiscalId = null): int
    {
        return DB::table('liasse_table_validations')->insertGetId([
            'user_id' => $userId, 'societe_id' => $societeId, 'fiscal_exercise_id' => $fiscalId,
            'exercice' => $year, 'tableau_code' => 'passage_fiscal', 'status' => 'draft',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    private function fiscalRows(): array
    {
        return collect(['source_documents', 'liasse_data', 'liasse_field_sources', 'liasse_table_validations'])
            ->mapWithKeys(fn (string $table): array => [
                $table => DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all(),
            ])
            ->all();
    }
}
