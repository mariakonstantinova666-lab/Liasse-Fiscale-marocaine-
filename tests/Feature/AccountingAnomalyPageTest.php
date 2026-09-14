<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Societe;
use App\Models\BalanceItem;
use App\Services\AccountingAnomalyInferenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccountingAnomalyPageTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        config(['accounting_anomaly.enabled' => true]);
        $user = User::factory()->create();
        $societe = Societe::create(['user_id' => $user->id, 'nom_societe' => 'IA test']);
        \App\Models\FiscalExercise::create(['user_id' => $user->id, 'societe_id' => $societe->id, 'exercice' => 2026]);
        foreach ([2025, 2026] as $year) {
            foreach (['6131', '4411'] as $account) {
                BalanceItem::create(['user_id' => $user->id, 'societe_id' => $societe->id, 'exercice' => $year,
                    'compte' => $account, 'libelle' => 'Compte '.$account, 'solde_debiteur' => 10, 'solde_crediteur' => 0]);
            }
        }
        $this->actingAs($user)->withSession(['annee_exercice' => 2026]);
        return [$user, $societe];
    }

    public function test_guest_is_rejected(): void
    {
        $this->get('/analyse-ia')->assertRedirect('/login');
        $this->post('/analyse-ia')->assertRedirect('/login');
    }

    public function test_initial_page_never_runs_inference(): void
    {
        $this->context();
        $this->mock(AccountingAnomalyInferenceService::class)->shouldNotReceive('infer');
        $this->get('/analyse-ia')->assertOk()->assertInertia(fn (Assert $p) => $p->component('AccountingAnomaly/Index')->where('exercise', 2026)->where('accountsCount', 2)->where('analysis', null));
    }

    public function test_unavailable_contexts_do_not_run_inference(): void
    {
        [$user, $societe] = $this->context();
        $this->mock(AccountingAnomalyInferenceService::class)->shouldNotReceive('infer');
        config(['accounting_anomaly.enabled' => false]);
        $this->post('/analyse-ia')->assertInertia(fn (Assert $p) => $p->where('unavailable', 'Le module d’analyse IA n’est pas activé sur ce serveur.'));
        config(['accounting_anomaly.enabled' => true]);
        foreach ([2025, 2026] as $year) {
            BalanceItem::where('exercice', $year)->delete();
            $this->post('/analyse-ia')->assertInertia(fn (Assert $p) => $p->where('unavailable', 'Analyse IA indisponible : une balance pour N et N-1 est nécessaire.'));
        }
        $this->withSession(['annee_exercice' => null]);
        $this->post('/analyse-ia')->assertInertia(fn (Assert $p) => $p->where('unavailable', 'Analyse IA indisponible : aucun exercice actif valide.'));
        $this->assertNull(session('annee_exercice'));
        Societe::create(['user_id' => $user->id, 'nom_societe' => 'Ambiguous']);
        $this->post('/analyse-ia')->assertInertia(fn (Assert $p) => $p->where('unavailable', 'Analyse IA indisponible : plusieurs sociétés nécessitent une sélection explicite.'));
        $this->actingAs(User::factory()->create());
        $this->post('/analyse-ia')->assertInertia(fn (Assert $p) => $p->where('unavailable', 'Analyse IA indisponible : aucune société disponible.'));
    }

    public function test_scoring_ranks_and_preserves_business_tables(): void
    {
        [$user, $societe] = $this->context();
        $tables = ['balance_items', 'liasse_data', 'source_documents', 'liasse_field_sources', 'fiscal_exercises', 'liasse_table_validations'];
        $before = [];
        foreach ($tables as $table) { $before[$table] = \DB::table($table)->get()->toJson(); }
        $this->mock(AccountingAnomalyInferenceService::class)->shouldReceive('infer')->once()
            ->withArgs(fn ($rows, $p, $n) => count($rows) === 2 && $p === 2025 && $n === 2026 && $rows[0]['user_id'] === $user->id && $rows[0]['societe_id'] === $societe->id)
            ->andReturn(['protocol' => 'account-transition-v1', 'model_version' => 'phase3b_v1', 'threshold' => -0.7, 'observations_count' => 2,
                'results' => [['account' => '6131', 'score_samples' => -0.8, 'flagged' => true], ['account' => '4411', 'score_samples' => -0.8, 'flagged' => true]]]);
        $this->post('/analyse-ia', ['exercise' => 2025, 'societe_id' => 999])->assertInertia(fn (Assert $p) => $p->where('analysis.results.0.account', '4411')->where('analysis.results.0.rank', 1)->where('analysis.flagged_count', 2));
        foreach ($tables as $table) { $this->assertSame($before[$table], \DB::table($table)->get()->toJson()); }
        $this->assertSame(2026, session('annee_exercice'));
    }

    public function test_zero_flagged_and_foreign_data_isolation(): void
    {
        $this->context();
        $foreign = User::factory()->create();
        $s = Societe::create(['user_id' => $foreign->id, 'nom_societe' => 'Foreign']);
        BalanceItem::create(['user_id' => $foreign->id, 'societe_id' => $s->id, 'exercice' => 2026, 'compte' => '9991', 'libelle' => 'Secret', 'solde_debiteur' => 999, 'solde_crediteur' => 0]);
        $this->mock(AccountingAnomalyInferenceService::class)->shouldReceive('infer')->once()->withArgs(fn ($rows) => count($rows) === 2)
            ->andReturn(['model_version' => 'phase3b_v1', 'threshold' => -0.7, 'observations_count' => 2, 'results' => [['account' => '6131', 'score_samples' => -0.5, 'flagged' => false], ['account' => '4411', 'score_samples' => -0.5, 'flagged' => false]]]);
        $this->post('/analyse-ia')->assertInertia(fn (Assert $p) => $p->where('analysis.flagged_count', 0)->where('analysis.results.0.label', 'Compte 4411'));
    }

    public function test_service_errors_are_sanitized(): void
    {
        $this->context();
        $this->mock(AccountingAnomalyInferenceService::class)->shouldReceive('infer')->twice()->andThrow(new \RuntimeException('IA_TIMEOUT'));
        $this->post('/analyse-ia')->assertInertia(fn (Assert $p) => $p->where('analysis', null)->where('unavailable', 'L’analyse IA a dépassé le temps autorisé.'));
        $this->post('/analyse-ia')->assertInertia(fn (Assert $p) => $p->where('analysis', null));
    }
}
