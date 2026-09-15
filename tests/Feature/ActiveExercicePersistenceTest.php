<?php

namespace Tests\Feature;

use App\Models\ActiveExercicePreference;
use App\Models\FiscalExercise;
use App\Models\Societe;
use App\Models\User;
use App\Services\ActiveExerciceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ActiveExercicePersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-14');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function context(array $years = [2025, 2026, 2027, 2028, 2029, 2030]): array
    {
        $user = User::factory()->create();
        $societe = Societe::create(['user_id' => $user->id, 'nom_societe' => 'Test']);
        foreach ($years as $year) {
            FiscalExercise::create(['user_id' => $user->id, 'societe_id' => $societe->id, 'exercice' => $year]);
        }
        $this->actingAs($user);

        return [$user, $societe, app(ActiveExerciceService::class)];
    }

    public function test_selection_persists_and_manual_change_updates_one_preference(): void
    {
        [$user, $societe, $service] = $this->context();
        $service->select(2026);
        $this->assertSame(2026, session('annee_exercice'));
        $this->assertDatabaseHas('active_exercice_preferences', ['user_id' => $user->id, 'societe_id' => $societe->id, 'exercice' => 2026]);
        $service->select(2025);
        $this->assertSame(2025, $service->current());
        $this->assertDatabaseCount('active_exercice_preferences', 1);
        $this->assertDatabaseHas('active_exercice_preferences', ['exercice' => 2025]);
    }

    public function test_logout_login_restores_selection_despite_future_exercises(): void
    {
        [$user, , $service] = $this->context();
        $service->select(2026);
        $this->post('/logout')->assertRedirect('/');
        $this->assertFalse(session()->has('annee_exercice'));
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
        $this->assertSame(2026, $service->current());
        $this->assertSame(2026, session('annee_exercice'));
        $this->assertContains(2030, $service->available());
    }

    public function test_valid_session_has_priority_over_preference(): void
    {
        [, , $service] = $this->context();
        $service->select(2028);
        session(['annee_exercice' => 2026]);
        $this->assertSame(2026, $service->current());
    }

    public function test_absent_preference_preserves_historical_fallback(): void
    {
        [, , $service] = $this->context();
        $this->assertSame(2030, $service->current());
        $this->assertSame(2030, session('annee_exercice'));
        $this->assertDatabaseCount('active_exercice_preferences', 0);
    }

    public function test_obsolete_preference_preserves_historical_fallback(): void
    {
        [$user, $societe, $service] = $this->context();
        ActiveExercicePreference::create(['user_id' => $user->id, 'societe_id' => $societe->id, 'exercice' => 2023]);
        session(['annee_exercice' => 2023]);
        $this->assertSame(2030, $service->current());
        $this->assertDatabaseHas('active_exercice_preferences', ['exercice' => 2023]);
    }

    public function test_empty_context_preserves_historical_civil_year_fallback(): void
    {
        [$user, $societe, $service] = $this->context([]);
        ActiveExercicePreference::create(['user_id' => $user->id, 'societe_id' => $societe->id, 'exercice' => 2023]);
        $this->assertSame(2026, $service->current());
        $this->assertFalse(session()->has('annee_exercice'));
        $this->assertDatabaseMissing('active_exercice_preferences', ['exercice' => 2030]);
    }

    public function test_preferences_are_isolated_between_users_and_societes(): void
    {
        [$first, $firstSociete, $service] = $this->context();
        $service->select(2028);
        [$second, $secondSociete] = $this->context();
        session()->forget('annee_exercice');
        $this->assertSame(2030, $service->current());
        $this->assertDatabaseHas('active_exercice_preferences', ['user_id' => $first->id, 'societe_id' => $firstSociete->id, 'exercice' => 2028]);
        $this->assertDatabaseMissing('active_exercice_preferences', ['user_id' => $second->id, 'societe_id' => $secondSociete->id]);
    }

    public function test_multiple_societes_preserve_historical_company_lookup(): void
    {
        [$user, , $service] = $this->context();
        Societe::create(['user_id' => $user->id, 'nom_societe' => 'Other']);
        $historicalId = Societe::where('user_id', $user->id)->value('id');
        $expected = FiscalExercise::where('user_id', $user->id)->where('societe_id', $historicalId)
            ->pluck('exercice')->map(fn ($year) => (int) $year)->sortDesc()->values()->all();
        $this->assertSame($expected, $service->available());
        $this->assertDatabaseCount('active_exercice_preferences', 0);
    }

    public function test_pending_preference_migration_does_not_query_missing_table(): void
    {
        $this->context();
        $service = new class extends ActiveExerciceService {
            protected function tableExists(string $table): bool
            {
                return $table !== 'active_exercice_preferences' && parent::tableExists($table);
            }
        };
        $service->select(2026);
        $this->assertSame(2026, $service->current());
        $this->assertDatabaseCount('active_exercice_preferences', 0);
    }

    public function test_settings_remain_accessible_without_available_exercise(): void
    {
        $this->context([]);
        $this->get('/settings')->assertOk();
        $this->assertFalse(session()->has('annee_exercice'));
    }
}
