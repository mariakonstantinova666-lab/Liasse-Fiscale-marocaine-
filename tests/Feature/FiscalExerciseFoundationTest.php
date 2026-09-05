<?php

namespace Tests\Feature;

use App\Models\BalanceItem;
use App\Models\FiscalExercise;
use App\Models\LiasseData;
use App\Models\LiasseFieldSource;
use App\Models\LiasseTableValidation;
use App\Models\Societe;
use App\Models\SourceDocument;
use App\Models\User;
use App\Services\FiscalExerciseBackfillService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class FiscalExerciseFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_fiscal_exercise_can_be_created_with_user_and_societe_relations(): void
    {
        [$user, $societe] = $this->userAndSociete();

        $exercise = FiscalExercise::create([
            'user_id' => $user->id,
            'societe_id' => $societe->id,
            'exercice' => 2026,
        ]);

        $this->assertTrue($exercise->user->is($user));
        $this->assertTrue($exercise->societe->is($societe));
    }

    public function test_societe_and_exercice_are_unique(): void
    {
        [$user, $societe] = $this->userAndSociete();
        FiscalExercise::create(['user_id' => $user->id, 'societe_id' => $societe->id, 'exercice' => 2026]);

        $this->expectException(QueryException::class);
        FiscalExercise::create(['user_id' => $user->id, 'societe_id' => $societe->id, 'exercice' => 2026]);
    }

    public function test_exercises_can_coexist_and_same_year_is_isolated_by_societe_and_user(): void
    {
        [$userA, $societeA] = $this->userAndSociete();
        [$userB, $societeB] = $this->userAndSociete();
        $societeA2 = Societe::create(['user_id' => $userA->id, 'nom_societe' => 'Societe A2']);

        foreach ([2025, 2026] as $exercice) {
            FiscalExercise::create(['user_id' => $userA->id, 'societe_id' => $societeA->id, 'exercice' => $exercice]);
        }
        FiscalExercise::create(['user_id' => $userA->id, 'societe_id' => $societeA2->id, 'exercice' => 2026]);
        FiscalExercise::create(['user_id' => $userB->id, 'societe_id' => $societeB->id, 'exercice' => 2026]);

        $this->assertDatabaseHas('fiscal_exercises', ['societe_id' => $societeA->id, 'exercice' => 2025]);
        $this->assertDatabaseHas('fiscal_exercises', ['societe_id' => $societeA->id, 'exercice' => 2026]);
        $this->assertDatabaseHas('fiscal_exercises', ['societe_id' => $societeA2->id, 'exercice' => 2026]);
        $this->assertDatabaseHas('fiscal_exercises', ['user_id' => $userB->id, 'societe_id' => $societeB->id, 'exercice' => 2026]);
    }

    public function test_backfill_unites_existing_years_without_modifying_business_data(): void
    {
        [$user, $societe] = $this->userAndSociete();
        $balance = BalanceItem::create([
            'user_id' => $user->id, 'societe_id' => $societe->id, 'exercice' => 2025,
            'compte' => '7000', 'libelle' => 'Produit', 'solde_debiteur' => 0, 'solde_crediteur' => 100,
        ]);
        $liasseData = LiasseData::create([
            'user_id' => $user->id, 'exercice' => 2026,
            'tableau_code' => 'passage_fiscal', 'cle' => 'total', 'valeur' => '42',
        ]);
        $document = SourceDocument::create([
            'user_id' => $user->id, 'societe_id' => $societe->id, 'exercice' => 2026,
            'document_type' => 'autre', 'tableau_code' => 'autre', 'original_name' => 'source.txt',
            'stored_path' => 'source-documents/source.txt', 'status' => SourceDocument::STATUS_IMPORTED,
        ]);
        $fieldSource = LiasseFieldSource::create([
            'user_id' => $user->id, 'societe_id' => $societe->id, 'exercice' => 2024,
            'tableau_code' => 'passage_fiscal', 'cle' => 'source', 'valeur' => '10',
        ]);
        $validation = LiasseTableValidation::create([
            'user_id' => $user->id, 'societe_id' => $societe->id, 'exercice' => 2023,
            'tableau_code' => 'passage_fiscal', 'status' => 'draft',
        ]);

        $created = app(FiscalExerciseBackfillService::class)->backfill();

        $this->assertSame(4, $created);
        $this->assertDatabaseHas('fiscal_exercises', ['user_id' => $user->id, 'societe_id' => $societe->id, 'exercice' => 2023]);
        $this->assertDatabaseHas('fiscal_exercises', ['user_id' => $user->id, 'societe_id' => $societe->id, 'exercice' => 2024]);
        $this->assertDatabaseHas('fiscal_exercises', ['user_id' => $user->id, 'societe_id' => $societe->id, 'exercice' => 2025]);
        $this->assertDatabaseHas('fiscal_exercises', ['user_id' => $user->id, 'societe_id' => $societe->id, 'exercice' => 2026]);
        $this->assertDatabaseHas('balance_items', ['id' => $balance->id, 'compte' => '7000', 'solde_crediteur' => 100]);
        $this->assertDatabaseHas('liasse_data', ['id' => $liasseData->id, 'valeur' => '42']);
        $this->assertDatabaseHas('source_documents', ['id' => $document->id, 'stored_path' => 'source-documents/source.txt']);
        $this->assertDatabaseHas('liasse_field_sources', ['id' => $fieldSource->id, 'valeur' => '10']);
        $this->assertDatabaseHas('liasse_table_validations', ['id' => $validation->id, 'status' => 'draft']);
        $this->assertSame(0, app(FiscalExerciseBackfillService::class)->backfill());
    }

    public function test_backfill_detects_ambiguous_liasse_data_without_choosing_a_societe(): void
    {
        $user = User::factory()->create();
        Societe::create(['user_id' => $user->id, 'nom_societe' => 'Societe 1']);
        Societe::create(['user_id' => $user->id, 'nom_societe' => 'Societe 2']);
        LiasseData::create([
            'user_id' => $user->id, 'exercice' => 2026,
            'tableau_code' => 'passage_fiscal', 'cle' => 'total', 'valeur' => '42',
        ]);

        try {
            app(FiscalExerciseBackfillService::class)->backfill();
            $this->fail('Le backfill devait signaler le rattachement ambigu de liasse_data.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('rattachement de liasse_data ambigu', $exception->getMessage());
            $this->assertStringContainsString('societes=2', $exception->getMessage());
        }

        $this->assertDatabaseCount('fiscal_exercises', 0);
        $this->assertDatabaseHas('liasse_data', ['user_id' => $user->id, 'exercice' => 2026, 'valeur' => '42']);
    }

    public function test_backfill_detects_liasse_data_without_any_societe(): void
    {
        $user = User::factory()->create();
        LiasseData::create([
            'user_id' => $user->id, 'exercice' => 2025,
            'tableau_code' => 'passage_fiscal', 'cle' => 'total', 'valeur' => '12',
        ]);

        try {
            app(FiscalExerciseBackfillService::class)->backfill();
            $this->fail('Le backfill devait signaler l absence de societe pour liasse_data.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('rattachement de liasse_data ambigu', $exception->getMessage());
            $this->assertStringContainsString('societes=0', $exception->getMessage());
        }

        $this->assertDatabaseCount('fiscal_exercises', 0);
        $this->assertDatabaseHas('liasse_data', ['user_id' => $user->id, 'exercice' => 2025, 'valeur' => '12']);
    }

    /** @return array{User, Societe} */
    private function userAndSociete(): array
    {
        $user = User::factory()->create();
        $societe = Societe::create(['user_id' => $user->id, 'nom_societe' => 'Societe test']);

        return [$user, $societe];
    }
}
