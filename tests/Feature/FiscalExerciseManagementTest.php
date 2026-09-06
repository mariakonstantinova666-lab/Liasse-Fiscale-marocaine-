<?php

namespace Tests\Feature;

use App\Models\FiscalExercise;
use App\Models\Societe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FiscalExerciseManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_exercise_is_created_available_and_immediately_active_without_business_data(): void
    {
        [$user, $societe] = $this->userAndSociete();

        $this->actingAs($user)
            ->post(route('fiscal-exercises.store'), ['exercice' => 2028])
            ->assertRedirect(route('settings.index'))
            ->assertSessionHas('annee_exercice', 2028)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('fiscal_exercises', [
            'user_id' => $user->id,
            'societe_id' => $societe->id,
            'exercice' => 2028,
        ]);
        $this->assertDatabaseCount('balance_items', 0);
        $this->assertDatabaseCount('liasse_data', 0);
        $this->assertDatabaseCount('source_documents', 0);
        $this->assertDatabaseCount('liasse_field_sources', 0);
        $this->assertDatabaseCount('liasse_table_validations', 0);

        $this->actingAs($user)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Index')
                ->where('activeExercice', 2028)
                ->where('availableExercices', [2028])
            );
    }

    public function test_duplicate_creation_is_idempotent_and_reactivates_the_exercise(): void
    {
        [$user] = $this->userAndSociete();

        $this->actingAs($user)->post(route('fiscal-exercises.store'), ['exercice' => 2026])->assertRedirect();
        session(['annee_exercice' => 2025]);
        $this->actingAs($user)
            ->post(route('fiscal-exercises.store'), ['exercice' => 2026])
            ->assertRedirect(route('settings.index'))
            ->assertSessionHas('annee_exercice', 2026);

        $this->assertDatabaseCount('fiscal_exercises', 1);
    }

    public function test_invalid_year_is_rejected(): void
    {
        [$user] = $this->userAndSociete();

        $this->actingAs($user)
            ->from(route('settings.index'))
            ->post(route('fiscal-exercises.store'), ['exercice' => 1899])
            ->assertRedirect(route('settings.index'))
            ->assertSessionHasErrors('exercice');

        $this->assertDatabaseCount('fiscal_exercises', 0);
    }

    public function test_creation_is_refused_without_exactly_one_societe(): void
    {
        $userWithoutSociete = User::factory()->create();
        $this->actingAs($userWithoutSociete)
            ->from(route('settings.index'))
            ->post(route('fiscal-exercises.store'), ['exercice' => 2026])
            ->assertRedirect(route('settings.index'))
            ->assertSessionHasErrors('exercice');

        [$user, $societe] = $this->userAndSociete();
        Societe::create(['user_id' => $user->id, 'nom_societe' => 'Seconde societe']);
        $this->actingAs($user)
            ->from(route('settings.index'))
            ->post(route('fiscal-exercises.store'), ['exercice' => 2026])
            ->assertRedirect(route('settings.index'))
            ->assertSessionHasErrors('exercice');

        $this->assertDatabaseMissing('fiscal_exercises', ['societe_id' => $societe->id, 'exercice' => 2026]);
        $this->assertDatabaseCount('fiscal_exercises', 0);
    }

    public function test_user_cannot_select_another_users_or_another_societes_exercise(): void
    {
        [$user, $societe] = $this->userAndSociete();
        [$otherUser, $otherSociete] = $this->userAndSociete();
        FiscalExercise::create(['user_id' => $otherUser->id, 'societe_id' => $otherSociete->id, 'exercice' => 2028]);
        $otherSocieteForUser = Societe::create(['user_id' => $user->id, 'nom_societe' => 'Autre societe']);
        FiscalExercise::create(['user_id' => $user->id, 'societe_id' => $otherSocieteForUser->id, 'exercice' => 2027]);

        $this->actingAs($user)
            ->from(route('settings.index'))
            ->post(route('exercice.select'), ['exercice' => 2028])
            ->assertSessionHasErrors('exercice');
        $this->actingAs($user)
            ->from(route('settings.index'))
            ->post(route('exercice.select'), ['exercice' => 2027])
            ->assertSessionHasErrors('exercice');

        $this->assertDatabaseMissing('fiscal_exercises', ['societe_id' => $societe->id, 'exercice' => 2028]);
    }

    /** @return array{User, Societe} */
    private function userAndSociete(): array
    {
        $user = User::factory()->create();
        $societe = Societe::create(['user_id' => $user->id, 'nom_societe' => 'Societe test']);

        return [$user, $societe];
    }
}
