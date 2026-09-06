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
use App\Services\FiscalExerciseResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class FiscalExercisePhase3AFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_columns_exist_and_are_nullable(): void
    {
        $expected = [
            'liasse_data' => ['societe_id', 'fiscal_exercise_id'],
            'source_documents' => ['fiscal_exercise_id'],
            'liasse_field_sources' => ['fiscal_exercise_id'],
            'liasse_table_validations' => ['fiscal_exercise_id'],
        ];

        foreach ($expected as $table => $columns) {
            $schemaColumns = collect(Schema::getColumns($table))->keyBy('name');

            foreach ($columns as $column) {
                $this->assertTrue(Schema::hasColumn($table, $column));
                $this->assertTrue((bool) $schemaColumns[$column]['nullable']);
            }
        }
    }

    public function test_legacy_rows_remain_valid_with_null_fiscal_links(): void
    {
        [$user, $societe] = $this->userAndSociete();
        $liasseData = LiasseData::create($this->liasseAttributes($user, 2026));
        $document = SourceDocument::create($this->documentAttributes($user, $societe, 2026));
        $fieldSource = LiasseFieldSource::create($this->fieldSourceAttributes($user, $societe, 2026));
        $validation = LiasseTableValidation::create($this->validationAttributes($user, $societe, 2026));

        $this->assertNull($liasseData->societe_id);
        $this->assertNull($liasseData->fiscal_exercise_id);
        $this->assertNull($document->fiscal_exercise_id);
        $this->assertNull($fieldSource->fiscal_exercise_id);
        $this->assertNull($validation->fiscal_exercise_id);
    }

    public function test_fiscal_exercise_relations_and_inverse_relations_work(): void
    {
        [$user, $societe] = $this->userAndSociete();
        $exercise = $this->exercise($user, $societe, 2026);

        $liasseData = LiasseData::unguarded(fn () => $exercise->liasseData()->create([
            ...$this->liasseAttributes($user, 2026),
            'societe_id' => $societe->id,
        ]));
        $document = $exercise->sourceDocuments()->create($this->documentAttributes($user, $societe, 2026));
        $fieldSource = $exercise->fieldSources()->create($this->fieldSourceAttributes($user, $societe, 2026));
        $validation = $exercise->tableValidations()->create($this->validationAttributes($user, $societe, 2026));

        $this->assertTrue($exercise->is($liasseData->fiscalExercise));
        $this->assertTrue($exercise->is($document->fiscalExercise));
        $this->assertTrue($exercise->is($fieldSource->fiscalExercise));
        $this->assertTrue($exercise->is($validation->fiscalExercise));
        $this->assertTrue($societe->is($liasseData->societe));
        $this->assertCount(1, $exercise->liasseData);
        $this->assertCount(1, $exercise->sourceDocuments);
        $this->assertCount(1, $exercise->fieldSources);
        $this->assertCount(1, $exercise->tableValidations);
    }

    public function test_resolver_finds_exact_and_future_empty_exercises_without_mutation(): void
    {
        [$user, $societe] = $this->userAndSociete();
        $current = $this->exercise($user, $societe, 2026);
        $future = $this->exercise($user, $societe, 2028);
        $before = $this->businessCounts();

        $this->assertTrue($current->is($this->resolver()->resolve($user->id, 2026, $societe->id)));
        $this->assertTrue($future->is($this->resolver()->resolve($user->id, 2028)));
        $this->assertSame($before, $this->businessCounts());
        $this->assertDatabaseCount('fiscal_exercises', 2);
    }

    public function test_resolver_refuses_missing_exercise_without_creating_it(): void
    {
        [$user, $societe] = $this->userAndSociete();

        try {
            $this->resolver()->resolve($user->id, 2027, $societe->id);
            $this->fail('La résolution stricte devait refuser un exercice fiscal absent.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Aucun exercice fiscal 2027', $exception->getMessage());
        }

        $this->assertDatabaseCount('fiscal_exercises', 0);
        $this->assertSame($this->emptyBusinessCounts(), $this->businessCounts());
    }

    public function test_implicit_resolution_refuses_zero_or_multiple_societes(): void
    {
        $userWithoutSociete = User::factory()->create();
        $this->expectResolverFailure(fn () => $this->resolver()->resolve($userWithoutSociete->id, 2026));

        [$user, $societe] = $this->userAndSociete();
        $otherSociete = Societe::create(['user_id' => $user->id, 'nom_societe' => 'Autre société']);
        $this->exercise($user, $societe, 2026);
        $this->exercise($user, $otherSociete, 2026);

        $this->expectResolverFailure(fn () => $this->resolver()->resolve($user->id, 2026));
    }

    public function test_resolver_refuses_another_users_societe(): void
    {
        [$user] = $this->userAndSociete();
        [$otherUser, $otherSociete] = $this->userAndSociete();
        $this->exercise($otherUser, $otherSociete, 2026);

        $this->expectResolverFailure(
            fn () => $this->resolver()->resolve($user->id, 2026, $otherSociete->id),
            'n’appartient pas'
        );
    }

    public function test_resolver_isolates_same_year_between_societes_and_years_within_societe(): void
    {
        [$user, $societeA] = $this->userAndSociete();
        $societeB = Societe::create(['user_id' => $user->id, 'nom_societe' => 'Seconde société']);
        $a2026 = $this->exercise($user, $societeA, 2026);
        $a2027 = $this->exercise($user, $societeA, 2027);
        $b2026 = $this->exercise($user, $societeB, 2026);

        $this->assertTrue($a2026->is($this->resolver()->resolve($user->id, 2026, $societeA->id)));
        $this->assertTrue($a2027->is($this->resolver()->resolve($user->id, 2027, $societeA->id)));
        $this->assertTrue($b2026->is($this->resolver()->resolve($user->id, 2026, $societeB->id)));
    }

    private function resolver(): FiscalExerciseResolver
    {
        return app(FiscalExerciseResolver::class);
    }

    private function expectResolverFailure(callable $callback, string $message = 'exactement une société'): void
    {
        try {
            $callback();
            $this->fail('La résolution stricte devait échouer.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString($message, $exception->getMessage());
        }
    }

    /** @return array{User, Societe} */
    private function userAndSociete(): array
    {
        $user = User::factory()->create();
        $societe = Societe::create(['user_id' => $user->id, 'nom_societe' => 'Société test']);

        return [$user, $societe];
    }

    private function exercise(User $user, Societe $societe, int $exercice): FiscalExercise
    {
        return FiscalExercise::create([
            'user_id' => $user->id,
            'societe_id' => $societe->id,
            'exercice' => $exercice,
        ]);
    }

    private function liasseAttributes(User $user, int $exercice): array
    {
        return ['user_id' => $user->id, 'exercice' => $exercice, 'tableau_code' => 'passage_fiscal', 'cle' => 'test', 'valeur' => '1'];
    }

    private function documentAttributes(User $user, Societe $societe, int $exercice): array
    {
        return [
            'user_id' => $user->id, 'societe_id' => $societe->id, 'exercice' => $exercice,
            'document_type' => 'autre', 'tableau_code' => 'autre', 'original_name' => 'test.txt',
            'stored_path' => 'source-documents/test.txt', 'size' => 0, 'status' => SourceDocument::STATUS_IMPORTED,
        ];
    }

    private function fieldSourceAttributes(User $user, Societe $societe, int $exercice): array
    {
        return [
            'user_id' => $user->id, 'societe_id' => $societe->id, 'exercice' => $exercice,
            'tableau_code' => 'passage_fiscal', 'cle' => 'test', 'valeur' => '1',
        ];
    }

    private function validationAttributes(User $user, Societe $societe, int $exercice): array
    {
        return [
            'user_id' => $user->id, 'societe_id' => $societe->id, 'exercice' => $exercice,
            'tableau_code' => 'passage_fiscal', 'status' => 'draft',
        ];
    }

    private function businessCounts(): array
    {
        return [
            'balance_items' => BalanceItem::count(),
            'liasse_data' => LiasseData::count(),
        ];
    }

    private function emptyBusinessCounts(): array
    {
        return ['balance_items' => 0, 'liasse_data' => 0];
    }
}
