<?php

namespace Tests\Feature;

use App\Http\Controllers\LiasseController;
use App\Models\BalanceItem;
use App\Models\User;
use App\Services\BalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

class CurrentExerciceTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_exercice_uses_the_session_value_for_2026(): void
    {
        session(['annee_exercice' => 2026]);

        $this->assertSame(2026, $this->currentExercice());
        $this->assertSame(2026, session('annee_exercice'));
    }

    public function test_current_exercice_preserves_a_future_session_value(): void
    {
        session(['annee_exercice' => 2027]);

        $this->assertSame(2027, $this->currentExercice());
        $this->assertSame(2027, session('annee_exercice'));
    }

    public function test_current_exercice_initializes_the_shared_2026_default_when_session_is_absent(): void
    {
        session()->forget('annee_exercice');

        $this->assertSame(2026, $this->currentExercice());
        $this->assertSame(2026, session('annee_exercice'));
    }

    public function test_liasse_uses_2026_as_n_and_requests_2025_as_n_minus_one(): void
    {
        $user = User::factory()->create();
        $societeId = DB::table('societes')->insertGetId([
            'user_id' => $user->id,
            'nom_societe' => 'Société test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([2026, 2025] as $exercice) {
            BalanceItem::create([
                'user_id' => $user->id,
                'societe_id' => $societeId,
                'compte' => (string) $exercice,
                'libelle' => "Balance {$exercice}",
                'solde_debiteur' => $exercice,
                'solde_crediteur' => 0,
                'exercice' => $exercice,
            ]);
        }

        session(['annee_exercice' => 2026]);
        [$n, $n1] = (new BalanceService())->lignesAvecPrecedent(
            $user->id,
            $this->currentExercice(),
        );

        $this->assertSame([2026], $n->pluck('exercice')->map(fn ($year) => (int) $year)->all());
        $this->assertSame([2025], $n1->pluck('exercice')->map(fn ($year) => (int) $year)->all());
    }

    public function test_save_uses_the_active_2026_exercice_and_not_2025(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['annee_exercice' => 2026])
            ->post(route('liasse.save', 'credit_bail'), [
                'f' => ['r0_c1' => 'Contrat 2026'],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('liasse_data', [
            'user_id' => $user->id,
            'exercice' => 2026,
            'tableau_code' => 'credit_bail',
            'cle' => 'r0_c1',
            'valeur' => 'Contrat 2026',
        ]);
        $this->assertDatabaseMissing('liasse_data', [
            'user_id' => $user->id,
            'exercice' => 2025,
            'tableau_code' => 'credit_bail',
            'cle' => 'r0_c1',
        ]);
    }

    public function test_save_preserves_a_future_2027_exercice(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['annee_exercice' => 2027])
            ->post(route('liasse.save', 'credit_bail'), [
                'f' => ['r0_c1' => 'Contrat 2027'],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('liasse_data', [
            'user_id' => $user->id,
            'exercice' => 2027,
            'tableau_code' => 'credit_bail',
            'cle' => 'r0_c1',
            'valeur' => 'Contrat 2027',
        ]);
        $this->assertDatabaseMissing('liasse_data', [
            'user_id' => $user->id,
            'exercice' => 2026,
            'tableau_code' => 'credit_bail',
            'cle' => 'r0_c1',
        ]);
    }

    private function currentExercice(): int
    {
        $method = new ReflectionMethod(LiasseController::class, 'currentExercice');

        return $method->invoke(new LiasseController());
    }
}
