<?php

namespace Tests\Feature;

use App\Models\BalanceItem;
use App\Models\LiasseData;
use App\Models\LiasseFieldSource;
use App\Models\Societe;
use App\Models\SourceDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class T03T26ManualDataTest extends TestCase
{
    use RefreshDatabase;

    private const T03_KEYS = [
        'reintegration_courante_0_label',
        'reintegration_courante_0_montant',
        'reintegrations_courantes_total',
        'reintegration_non_courante_0_label',
        'reintegration_non_courante_0_montant',
        'reintegrations_non_courantes_total',
        'deductions_courantes_total',
        'deductions_non_courantes_total',
        'reports_deficitaires_total',
    ];

    private const T26_KEYS = [
        'is_ca_taxable_taux', 'is_ca_taxable_montant',
        'is_ca_exonere_taux', 'is_ca_exonere_montant',
        'is_ca_taux_reduit_taux', 'is_ca_taux_reduit_montant',
        'is_autres_taxables_taux', 'is_autres_taxables_montant',
        'is_autres_exploitation_taux', 'is_autres_exploitation_montant',
        'is_produits_financiers_taux', 'is_produits_financiers_montant',
        'is_subventions_taux', 'is_subventions_montant',
        'is_denominateur_taux', 'is_denominateur_montant',
        'is_montant_du_taux', 'is_montant_du_montant',
    ];

    public function test_t03_exposes_exactly_the_nine_authorized_manual_fields(): void
    {
        [$user] = $this->userAndSociete();

        $response = $this->actingAs($user)
            ->withSession(['annee_exercice' => 2026])
            ->get(route('liasse.passage_fiscal'))
            ->assertOk();

        foreach (self::T03_KEYS as $key) {
            $response->assertSee('name="f['.$key.']"', false);
        }

        $this->assertSame(9, substr_count($response->getContent(), 'name="f['));
        $response->assertDontSee('name="f[benefice_net_comptable]"', false)
            ->assertDontSee('name="f[benefice_net_fiscal]"', false);
    }

    public function test_t03_updates_only_allowed_keys_and_preserves_other_data_and_document_trace(): void
    {
        [$user, $societe] = $this->userAndSociete();
        $this->liasseData($user, 2026, 'passage_fiscal', 'reintegration_courante_0_label', 'Document initial');
        $this->liasseData($user, 2026, 'passage_fiscal', 'calculated_snapshot', 'preserve-me');

        $document = SourceDocument::create([
            'user_id' => $user->id, 'societe_id' => $societe->id, 'exercice' => 2026,
            'document_type' => 'dossier_fiscal_complet', 'tableau_code' => 'passage_fiscal',
            'original_name' => 'source.xlsx', 'stored_path' => 'source-documents/source.xlsx',
            'status' => SourceDocument::STATUS_EXTRACTED,
        ]);
        $trace = LiasseFieldSource::create([
            'user_id' => $user->id, 'societe_id' => $societe->id,
            'source_document_id' => $document->id, 'exercice' => 2026,
            'tableau_code' => 'passage_fiscal', 'cle' => 'reintegration_courante_0_label',
            'valeur' => 'Document initial', 'source_type' => 'document', 'status' => 'extracted',
        ]);

        $this->actingAs($user)->withSession(['annee_exercice' => 2026])
            ->post(route('liasse.save', 'passage_fiscal'), ['f' => [
                'reintegration_courante_0_label' => 'Correction manuelle',
                'unauthorized_key' => 'ignored',
            ]])->assertRedirect();

        $this->assertDatabaseHas('liasse_data', ['user_id' => $user->id, 'exercice' => 2026, 'tableau_code' => 'passage_fiscal', 'cle' => 'reintegration_courante_0_label', 'valeur' => 'Correction manuelle']);
        $this->assertDatabaseHas('liasse_data', ['user_id' => $user->id, 'exercice' => 2026, 'tableau_code' => 'passage_fiscal', 'cle' => 'calculated_snapshot', 'valeur' => 'preserve-me']);
        $this->assertDatabaseMissing('liasse_data', ['user_id' => $user->id, 'tableau_code' => 'passage_fiscal', 'cle' => 'unauthorized_key']);
        $this->assertDatabaseHas('liasse_field_sources', ['id' => $trace->id, 'source_document_id' => $document->id, 'valeur' => 'Document initial']);
    }

    public function test_t03_manual_totals_feed_calculations_without_making_balance_cells_editable(): void
    {
        [$user, $societe] = $this->userAndSociete();
        $this->balance($user, $societe, 2026, '7000', 0, 1000);
        $this->balance($user, $societe, 2026, '6000', 200, 0);

        $this->actingAs($user)->withSession(['annee_exercice' => 2026])
            ->post(route('liasse.save', 'passage_fiscal'), ['f' => [
                'reintegrations_courantes_total' => '100',
                'reintegrations_non_courantes_total' => '50',
                'deductions_courantes_total' => '25',
                'deductions_non_courantes_total' => '5',
                'reports_deficitaires_total' => '20',
            ]])->assertRedirect();

        $this->actingAs($user)->withSession(['annee_exercice' => 2026])
            ->get(route('liasse.passage_fiscal'))
            ->assertOk()
            ->assertSee('800.00')
            ->assertSee('900.00')
            ->assertDontSee('name="f[benefice_net_comptable]"', false);
    }

    public function test_t03_save_is_isolated_to_the_active_exercise(): void
    {
        [$user] = $this->userAndSociete();
        $this->liasseData($user, 2025, 'passage_fiscal', 'deductions_courantes_total', '25-old');
        $this->liasseData($user, 2026, 'passage_fiscal', 'deductions_courantes_total', '26-old');

        $this->actingAs($user)->withSession(['annee_exercice' => 2026])
            ->post(route('liasse.save', 'passage_fiscal'), ['f' => ['deductions_courantes_total' => '26-new']])
            ->assertRedirect();

        $this->assertDatabaseHas('liasse_data', ['user_id' => $user->id, 'exercice' => 2025, 'cle' => 'deductions_courantes_total', 'valeur' => '25-old']);
        $this->assertDatabaseHas('liasse_data', ['user_id' => $user->id, 'exercice' => 2026, 'cle' => 'deductions_courantes_total', 'valeur' => '26-new']);
    }

    public function test_t26_get_reloads_all_existing_values(): void
    {
        [$user] = $this->userAndSociete();
        foreach (self::T26_KEYS as $index => $key) {
            $this->liasseData($user, 2026, 'calcul_is_encouragees', $key, 'saved-'.$index);
        }

        $response = $this->actingAs($user)->withSession(['annee_exercice' => 2026])
            ->get(route('liasse.calcul_is_encouragees'))->assertOk();

        foreach (self::T26_KEYS as $index => $key) {
            $response->assertSee('name="f['.$key.']" value="saved-'.$index.'"', false);
        }
    }

    public function test_t26_save_then_reload_persists_the_eighteen_fields(): void
    {
        [$user] = $this->userAndSociete();
        $fields = [];
        foreach (self::T26_KEYS as $index => $key) {
            $fields[$key] = 'roundtrip-'.$index;
        }

        $this->actingAs($user)->withSession(['annee_exercice' => 2026])
            ->post(route('liasse.save', 'calcul_is_encouragees'), ['f' => $fields])
            ->assertRedirect();

        $this->assertSame(18, LiasseData::where('user_id', $user->id)->where('exercice', 2026)->where('tableau_code', 'calcul_is_encouragees')->count());
        $response = $this->actingAs($user)->withSession(['annee_exercice' => 2026])
            ->get(route('liasse.calcul_is_encouragees'))->assertOk();
        foreach ($fields as $key => $value) {
            $response->assertSee('name="f['.$key.']" value="'.$value.'"', false);
        }
    }

    public function test_t26_save_and_reload_are_isolated_to_the_active_exercise(): void
    {
        [$user] = $this->userAndSociete();
        $key = self::T26_KEYS[0];
        $this->liasseData($user, 2025, 'calcul_is_encouragees', $key, '2025-value');
        $this->liasseData($user, 2026, 'calcul_is_encouragees', $key, '2026-old');

        $this->actingAs($user)->withSession(['annee_exercice' => 2026])
            ->post(route('liasse.save', 'calcul_is_encouragees'), ['f' => [$key => '2026-new']])
            ->assertRedirect();

        $this->assertDatabaseHas('liasse_data', ['user_id' => $user->id, 'exercice' => 2025, 'tableau_code' => 'calcul_is_encouragees', 'cle' => $key, 'valeur' => '2025-value']);
        $this->assertDatabaseHas('liasse_data', ['user_id' => $user->id, 'exercice' => 2026, 'tableau_code' => 'calcul_is_encouragees', 'cle' => $key, 'valeur' => '2026-new']);
        $this->actingAs($user)->withSession(['annee_exercice' => 2026])
            ->get(route('liasse.calcul_is_encouragees'))
            ->assertSee('value="2026-new"', false)
            ->assertDontSee('value="2025-value"', false);
    }

    /** @return array{User, Societe} */
    private function userAndSociete(): array
    {
        $user = User::factory()->create();
        $societe = Societe::create(['user_id' => $user->id, 'nom_societe' => 'Société test']);

        return [$user, $societe];
    }

    private function liasseData(User $user, int $exercice, string $tableau, string $key, string $value): void
    {
        LiasseData::create(['user_id' => $user->id, 'exercice' => $exercice, 'tableau_code' => $tableau, 'cle' => $key, 'valeur' => $value]);
    }

    private function balance(User $user, Societe $societe, int $exercice, string $compte, float $debit, float $credit): void
    {
        BalanceItem::create([
            'user_id' => $user->id, 'societe_id' => $societe->id, 'exercice' => $exercice,
            'compte' => $compte, 'libelle' => $compte,
            'solde_debiteur' => $debit, 'solde_crediteur' => $credit,
        ]);
    }
}
