<?php

namespace Tests\Feature;

use App\Models\BalanceItem;
use App\Models\Societe;
use App\Models\User;
use App\Services\ActiveExerciceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ActiveExerciceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_preserves_every_existing_session_exercice(): void
    {
        foreach ([2024, 2025, 2026, 2027] as $exercice) {
            session(['annee_exercice' => $exercice]);

            $this->assertSame($exercice, $this->service()->current());
            $this->assertSame($exercice, session('annee_exercice'));
        }
    }

    public function test_current_uses_latest_available_exercice_when_session_is_absent(): void
    {
        [$user, $societe] = $this->userAndSociete();
        $this->actingAs($user);
        $this->createBalances($user, $societe, [2024, 2025, 2026]);
        session()->forget('annee_exercice');

        $this->assertSame(2026, $this->service()->current());
        $this->assertSame(2026, session('annee_exercice'));
    }

    public function test_current_uses_latest_of_two_available_exercices(): void
    {
        [$user, $societe] = $this->userAndSociete();
        $this->actingAs($user);
        $this->createBalances($user, $societe, [2024, 2025]);
        session()->forget('annee_exercice');

        $this->assertSame(2025, $this->service()->current());
    }

    public function test_current_uses_current_year_when_no_balance_exists(): void
    {
        Carbon::setTestNow('2031-06-15');
        [$user] = $this->userAndSociete();
        $this->actingAs($user);
        session()->forget('annee_exercice');

        $this->assertSame(2031, $this->service()->current());
        $this->assertFalse(session()->has('annee_exercice'));

        $this->createBalance($user, Societe::where('user_id', $user->id)->firstOrFail(), 2025);

        $this->assertSame(2025, $this->service()->current());
        $this->assertSame(2025, session('annee_exercice'));

        Carbon::setTestNow();
    }

    public function test_available_is_scoped_distinct_and_sorted_descending(): void
    {
        [$user, $societe] = $this->userAndSociete();
        [$otherUser, $otherSociete] = $this->userAndSociete();
        $this->actingAs($user);

        $this->createBalances($user, $societe, [2024, 2026, 2025, 2026]);
        $this->createBalances($otherUser, $otherSociete, [2030]);
        $this->createBalance($otherUser, $societe, 2029);

        $this->assertSame([2026, 2025, 2024], $this->service()->available());
    }

    public function test_select_sets_only_an_available_exercice(): void
    {
        [$user, $societe] = $this->userAndSociete();
        $this->actingAs($user);
        $this->createBalances($user, $societe, [2024, 2025]);

        $this->service()->select(2024);

        $this->assertSame(2024, session('annee_exercice'));
    }

    public function test_select_rejects_an_unavailable_exercice(): void
    {
        [$user, $societe] = $this->userAndSociete();
        $this->actingAs($user);
        $this->createBalances($user, $societe, [2025]);

        $this->expectException(ValidationException::class);

        $this->service()->select(2024);
    }

    private function service(): ActiveExerciceService
    {
        return app(ActiveExerciceService::class);
    }

    /** @return array{User, Societe} */
    private function userAndSociete(): array
    {
        $user = User::factory()->create();
        $societe = Societe::create([
            'user_id' => $user->id,
            'nom_societe' => 'Société test',
        ]);

        return [$user, $societe];
    }

    /** @param int[] $exercices */
    private function createBalances(User $user, Societe $societe, array $exercices): void
    {
        foreach ($exercices as $index => $exercice) {
            $this->createBalance($user, $societe, $exercice, $index);
        }
    }

    private function createBalance(User $user, Societe $societe, int $exercice, int $index = 0): void
    {
        BalanceItem::create([
            'user_id' => $user->id,
            'societe_id' => $societe->id,
            'compte' => (string) ($exercice * 10 + $index),
            'libelle' => "Balance {$exercice}",
            'solde_debiteur' => 1,
            'solde_crediteur' => 0,
            'exercice' => $exercice,
        ]);
    }
}
