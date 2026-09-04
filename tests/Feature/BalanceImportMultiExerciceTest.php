<?php

namespace Tests\Feature;

use App\Models\BalanceItem;
use App\Models\LiasseData;
use App\Models\Societe;
use App\Models\User;
use App\Services\ActiveExerciceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Tests\TestCase;

class BalanceImportMultiExerciceTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_preserves_every_existing_active_exercice(): void
    {
        [$user] = $this->userAndSociete();

        foreach ([[2026, 2025], [2026, 2024], [2025, 2026], [2024, 2026]] as [$active, $imported]) {
            $this->actingAs($user)
                ->withSession(['annee_exercice' => $active])
                ->post(route('balance.import'), [
                    'annee' => $imported,
                    'balance' => $this->csv([["{$imported}01", "Compte {$imported}", 100, 0]]),
                ])
                ->assertRedirect()
                ->assertSessionHas('annee_exercice', $active);
        }
    }

    public function test_first_import_after_an_empty_dashboard_becomes_the_natural_context(): void
    {
        [$user] = $this->userAndSociete();
        $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $this->assertFalse(session()->has('annee_exercice'));

        $this->post(route('balance.import'), [
            'annee' => 2025,
            'balance' => $this->csv([['1000', 'Capital', 100, 0]]),
        ])->assertRedirect();

        $this->assertFalse(session()->has('annee_exercice'));

        $this->get(route('dashboard'))->assertOk();
        $this->assertSame(2025, session('annee_exercice'));
    }

    public function test_import_creates_only_rows_for_the_requested_exercice_and_reports_it(): void
    {
        [$user, $societe] = $this->userAndSociete();

        $response = $this->actingAs($user)
            ->withSession(['annee_exercice' => 2026])
            ->post(route('balance.import'), [
                'annee' => 2024,
                'balance' => $this->csv([
                    ['1000', 'Capital', 100, 0],
                    ['2000', 'Immobilisation', 0, 50],
                ]),
            ]);

        $response->assertRedirect()
            ->assertSessionHas('success', fn ($message) => str_contains($message, '2 lignes')
                && str_contains($message, 'exercice 2024'));
        $this->assertSame([2024], BalanceItem::query()->distinct()->pluck('exercice')->map(fn ($year) => (int) $year)->all());
        $this->assertDatabaseCount('balance_items', 2);
        $this->assertDatabaseHas('balance_items', [
            'user_id' => $user->id,
            'societe_id' => $societe->id,
            'exercice' => 2024,
            'compte' => '1000',
        ]);
    }

    public function test_reimport_replaces_only_the_target_user_society_and_exercice(): void
    {
        [$user, $societe] = $this->userAndSociete();
        $otherSociete = Societe::create(['user_id' => $user->id, 'nom_societe' => 'Autre société']);
        [$otherUser, $otherUserSociete] = $this->userAndSociete();

        $this->balance($user, $societe, 2024, 'OLD-2024');
        $this->balance($user, $societe, 2025, 'OLD-2025');
        $this->balance($user, $societe, 2026, 'OLD-2026');
        $this->balance($user, $otherSociete, 2025, 'OTHER-SOCIETY');
        $this->balance($otherUser, $otherUserSociete, 2025, 'OTHER-USER');

        $this->actingAs($user)->post(route('balance.import'), [
            'annee' => 2025,
            'balance' => $this->csv([['NEW-2025', 'Nouvelle ligne', 25, 0]]),
        ])->assertRedirect();

        $this->assertDatabaseMissing('balance_items', ['societe_id' => $societe->id, 'compte' => 'OLD-2025']);
        $this->assertDatabaseHas('balance_items', ['societe_id' => $societe->id, 'exercice' => 2025, 'compte' => 'NEW-2025']);
        foreach (['OLD-2024', 'OLD-2026', 'OTHER-SOCIETY', 'OTHER-USER'] as $compte) {
            $this->assertDatabaseHas('balance_items', ['compte' => $compte]);
        }
    }

    public function test_dynamic_and_reasonable_future_years_are_accepted_and_available_immediately(): void
    {
        [$user] = $this->userAndSociete();
        $this->actingAs($user);

        foreach ([2027, now()->year + 10] as $year) {
            $this->post(route('balance.import'), [
                'annee' => $year,
                'balance' => $this->csv([["{$year}01", "Compte {$year}", 1, 0]]),
            ])->assertRedirect()->assertSessionHasNoErrors();
        }

        $this->assertSame(
            collect([2027, now()->year + 10])->unique()->sortDesc()->values()->all(),
            app(ActiveExerciceService::class)->available(),
        );
    }

    public function test_invalid_years_are_rejected(): void
    {
        [$user] = $this->userAndSociete();

        foreach ([null, 'année', '2025.5', 1899, now()->year + 11] as $year) {
            $this->actingAs($user)
                ->post(route('balance.import'), [
                    'annee' => $year,
                    'balance' => $this->csv([['1000', 'Capital', 1, 0]]),
                ])
                ->assertSessionHasErrors('annee');
        }

        $this->assertDatabaseCount('balance_items', 0);
    }

    public function test_liasse_data_is_preserved_when_a_balance_is_reimported(): void
    {
        [$user, $societe] = $this->userAndSociete();
        $this->balance($user, $societe, 2025, 'OLD');
        LiasseData::create([
            'user_id' => $user->id,
            'exercice' => 2025,
            'tableau_code' => 'credit_bail',
            'cle' => 'r0_c1',
            'valeur' => 'À conserver',
        ]);

        $this->actingAs($user)->post(route('balance.import'), [
            'annee' => 2025,
            'balance' => $this->csv([['NEW', 'Nouvelle ligne', 1, 0]]),
        ])->assertRedirect();

        $this->assertDatabaseHas('liasse_data', [
            'user_id' => $user->id,
            'exercice' => 2025,
            'valeur' => 'À conserver',
        ]);
    }

    public function test_empty_or_non_importable_file_never_destroys_an_existing_balance(): void
    {
        [$user, $societe] = $this->userAndSociete();

        foreach ([
            [UploadedFile::fake()->createWithContent('balance.csv', ''), "Erreur lors de l'import. La balance existante a été conservée."],
            [UploadedFile::fake()->createWithContent('balance.csv', "compte,libelle,debit,credit\n,,,\n"), "Le fichier ne contient aucune ligne de balance importable."],
        ] as [$file, $expectedError]) {
            BalanceItem::query()->delete();
            $this->balance($user, $societe, 2025, 'EXISTING');

            $this->actingAs($user)
                ->withSession(['annee_exercice' => 2026])
                ->post(route('balance.import'), ['annee' => 2025, 'balance' => $file])
                ->assertRedirect()
                ->assertSessionHas('annee_exercice', 2026)
                ->assertSessionMissing('success')
                ->assertSessionHas('error', $expectedError);

            $this->assertDatabaseHas('balance_items', ['societe_id' => $societe->id, 'compte' => 'EXISTING']);
            $this->assertDatabaseCount('balance_items', 1);
        }
    }

    public function test_exception_during_replacement_rolls_back_deletion_and_partial_inserts(): void
    {
        [$user, $societe] = $this->userAndSociete();
        $this->balance($user, $societe, 2025, 'EXISTING');

        BalanceItem::creating(function (BalanceItem $item) {
            if ($item->compte === 'THROW') {
                throw new RuntimeException('Insertion interrompue pour le test.');
            }
        });

        try {
            $this->actingAs($user)->post(route('balance.import'), [
                'annee' => 2025,
                'balance' => $this->csv([
                    ['FIRST', 'Première nouvelle ligne', 1, 0],
                    ['THROW', 'Ligne en erreur', 2, 0],
                ]),
            ])->assertRedirect()->assertSessionHas('error');
        } finally {
            BalanceItem::flushEventListeners();
        }

        $this->assertDatabaseHas('balance_items', ['societe_id' => $societe->id, 'compte' => 'EXISTING']);
        $this->assertDatabaseMissing('balance_items', ['societe_id' => $societe->id, 'compte' => 'FIRST']);
        $this->assertDatabaseMissing('balance_items', ['societe_id' => $societe->id, 'compte' => 'THROW']);
        $this->assertDatabaseCount('balance_items', 1);
    }

    /** @return array{User, Societe} */
    private function userAndSociete(): array
    {
        $user = User::factory()->create();
        $societe = Societe::create([
            'user_id' => $user->id,
            'nom_societe' => 'Société de test',
        ]);

        return [$user, $societe];
    }

    private function balance(User $user, Societe $societe, int $year, string $compte): BalanceItem
    {
        return BalanceItem::create([
            'user_id' => $user->id,
            'societe_id' => $societe->id,
            'compte' => $compte,
            'libelle' => "Balance {$year}",
            'solde_debiteur' => 1,
            'solde_crediteur' => 0,
            'exercice' => $year,
        ]);
    }

    /** @param array<int, array<int, int|string>> $rows */
    private function csv(array $rows): UploadedFile
    {
        $content = "compte,libelle,debit,credit\n";
        foreach ($rows as $row) {
            $content .= implode(',', $row) . "\n";
        }

        return UploadedFile::fake()->createWithContent('balance.csv', $content);
    }
}
