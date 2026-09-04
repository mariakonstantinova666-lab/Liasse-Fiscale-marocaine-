<?php

namespace Tests\Feature;

use App\Models\BalanceItem;
use App\Models\LiasseData;
use App\Models\Societe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ActiveExerciceSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_is_protected_for_an_unauthenticated_user(): void
    {
        $this->post(route('exercice.select'), ['exercice' => 2025])
            ->assertRedirect(route('login'));
    }

    public function test_available_exercices_can_be_selected_in_both_directions(): void
    {
        [$user, $societe] = $this->userAndSociete();
        $this->createBalances($user, $societe, [2024, 2025, 2026, 2027]);

        foreach ([[2026, 2025], [2026, 2024], [2025, 2026], [2026, 2027]] as [$from, $to]) {
            $this->actingAs($user)
                ->withSession(['annee_exercice' => $from])
                ->from(route('dashboard'))
                ->post(route('exercice.select'), ['exercice' => $to])
                ->assertRedirect(route('dashboard'))
                ->assertSessionHas('annee_exercice', $to);
        }
    }

    public function test_an_unavailable_or_other_users_exercice_is_rejected(): void
    {
        [$user, $societe] = $this->userAndSociete();
        [$otherUser, $otherSociete] = $this->userAndSociete();
        $this->createBalances($user, $societe, [2025]);
        $this->createBalances($otherUser, $otherSociete, [2027]);

        $this->actingAs($user)
            ->withSession(['annee_exercice' => 2025])
            ->from(route('dashboard'))
            ->post(route('exercice.select'), ['exercice' => 2027])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('exercice')
            ->assertSessionHas('annee_exercice', 2025);
    }

    public function test_an_exercice_from_another_society_is_rejected_in_the_current_single_society_context(): void
    {
        [$user, $societe] = $this->userAndSociete();
        $otherSociete = Societe::create([
            'user_id' => $user->id,
            'nom_societe' => 'Autre société',
        ]);
        $this->createBalances($user, $societe, [2025]);
        $this->createBalances($user, $otherSociete, [2027]);

        $this->actingAs($user)
            ->withSession(['annee_exercice' => 2025])
            ->from(route('dashboard'))
            ->post(route('exercice.select'), ['exercice' => 2027])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('exercice')
            ->assertSessionHas('annee_exercice', 2025);
    }

    public function test_selection_does_not_mutate_balances_or_liasse_data(): void
    {
        [$user, $societe] = $this->userAndSociete();
        $this->createBalances($user, $societe, [2024, 2025, 2026]);
        LiasseData::create([
            'user_id' => $user->id,
            'exercice' => 2026,
            'tableau_code' => 'credit_bail',
            'cle' => 'r0_c1',
            'valeur' => 'Contrat conservé',
        ]);

        $balancesBefore = BalanceItem::query()->orderBy('id')->get()->toArray();
        $liasseBefore = LiasseData::query()->orderBy('id')->get()->toArray();

        $this->actingAs($user)
            ->withSession(['annee_exercice' => 2026])
            ->from(route('liasse.cpc'))
            ->post(route('exercice.select'), ['exercice' => 2024])
            ->assertRedirect(route('liasse.cpc'));

        $this->assertSame($balancesBefore, BalanceItem::query()->orderBy('id')->get()->toArray());
        $this->assertSame($liasseBefore, LiasseData::query()->orderBy('id')->get()->toArray());
    }

    public function test_dashboard_receives_the_global_inertia_exercice_context(): void
    {
        [$user, $societe] = $this->userAndSociete();
        $this->createBalances($user, $societe, [2024, 2026, 2025]);

        $this->actingAs($user)
            ->withSession(['annee_exercice' => 2025])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('activeExercice', 2025)
                ->where('availableExercices', [2026, 2025, 2024])
            );
    }

    public function test_blade_layout_receives_the_same_exercice_context(): void
    {
        [$user, $societe] = $this->userAndSociete();
        $this->createBalances($user, $societe, [2024, 2026, 2025]);
        $this->actingAs($user);
        session(['annee_exercice' => 2025]);

        $view = view('layouts.app');
        $view->render();

        $this->assertSame(2025, $view->getData()['activeExercice']);
        $this->assertSame([2026, 2025, 2024], $view->getData()['availableExercices']);
    }

    public function test_blade_selector_is_disabled_when_no_balance_is_available(): void
    {
        [$user] = $this->userAndSociete();
        $this->actingAs($user);

        $html = view('layouts.app')->render();

        $this->assertStringContainsString('Aucune balance disponible', $html);
        $this->assertMatchesRegularExpression('/<select[^>]*disabled[^>]*>/', $html);
    }

    private function userAndSociete(): array
    {
        $user = User::factory()->create();
        $societe = Societe::create([
            'user_id' => $user->id,
            'nom_societe' => 'Société test',
        ]);

        return [$user, $societe];
    }

    private function createBalances(User $user, Societe $societe, array $exercices): void
    {
        foreach ($exercices as $index => $exercice) {
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
}
