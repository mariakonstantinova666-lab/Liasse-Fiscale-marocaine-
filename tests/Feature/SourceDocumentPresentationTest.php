<?php

namespace Tests\Feature;

use App\Models\LiasseData;
use App\Models\LiasseFieldSource;
use App\Models\Societe;
use App\Models\SourceDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SourceDocumentPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_t13_labels_and_values_are_rendered_without_changing_stored_data(): void
    {
        $labels = [
            'montant_capital' => 'Montant du capital social',
            'r0_c1' => "Nom et prénom de l'associé — Associé 1",
            'r0_c3' => 'Identifiant fiscal — Associé 1',
            'r0_c4' => 'CIN — Associé 1',
            'r0_c6' => 'Adresse — Associé 1',
            'r0_c7' => 'Nombre de titres — Exercice précédent — Associé 1',
            'r0_c8' => 'Nombre de titres — Exercice actuel — Associé 1',
            'r0_c9' => 'Valeur nominale de chaque action ou part sociale — Associé 1',
            'r0_c10' => 'Capital souscrit — Associé 1',
            'r0_c11' => 'Capital appelé — Associé 1',
            'r0_c12' => 'Capital libéré — Associé 1',
            'r1_c1' => "Nom et prénom de l'associé — Associé 2",
            'total_c10' => 'Total — Capital souscrit',
        ];
        $fields = [];
        foreach ($labels as $key => $label) {
            $fields[] = $this->field('repartition_capital', $key, '001 234,50 <br> & valeur '.$key);
        }
        $document = $this->document($fields);
        LiasseData::create([
            'user_id' => $document->user_id, 'exercice' => 2026,
            'tableau_code' => 'repartition_capital', 'cle' => 'r0_c1', 'valeur' => 'Valeur métier différente',
        ]);
        LiasseFieldSource::create([
            'user_id' => $document->user_id, 'societe_id' => $document->societe_id,
            'source_document_id' => $document->id, 'exercice' => 2026,
            'tableau_code' => 'repartition_capital', 'cle' => 'r0_c1', 'valeur' => 'Trace conservée',
        ]);
        $extractionBefore = $document->extraction->getAttributes();
        $dataBefore = LiasseData::all()->toArray();
        $sourcesBefore = LiasseFieldSource::all()->toArray();

        $response = $this->get(route('source-documents.show', $document))->assertOk()
            ->assertSee('Champ / information')->assertSee('Tableau concerné')
            ->assertDontSee('Cle proposee')->assertDontSee('Ligne - - -')
            ->assertDontSee('Valeur métier différente');
        foreach ($fields as $field) {
            $response->assertSeeInOrder([$labels[$field['cle']], 'T13 — Répartition du capital', $field['valeur']]);
        }
        $this->assertSame($extractionBefore, $document->extraction->fresh()->getAttributes());
        $this->assertSame($dataBefore, LiasseData::all()->toArray());
        $this->assertSame($sourcesBefore, LiasseFieldSource::all()->toArray());
    }

    public function test_same_key_in_other_tables_is_not_given_a_t13_label(): void
    {
        $document = $this->document([
            $this->field('locations_baux', 'r0_c1', 'Bien loué'),
            $this->field('credit_bail', 'r0_c1', 'Contrat'),
        ]);
        $this->get(route('source-documents.show', $document))->assertOk()
            ->assertSeeInOrder(['Nature du bien loué — Ligne 1', 'T19 — Locations et baux', 'Bien loué', 'r0_c1', 'T07 — Crédit-bail', 'Contrat'])
            ->assertDontSee("Nom et prénom de l'associé");
    }

    public function test_unknown_keys_and_tables_are_displayed_unchanged(): void
    {
        $document = $this->document([
            $this->field('repartition_capital', 'r0_c99', 'Inconnu'),
            $this->field('table_inconnue', 'montant_capital', '0000.00'),
            $this->field('repartition_capital', 'champ_inconnu', 'Texte'),
        ]);
        $this->get(route('source-documents.show', $document))->assertOk()
            ->assertSeeInOrder(['r0_c99', 'T13 — Répartition du capital', 'Inconnu', 'montant_capital', 'table_inconnue', '0000.00', 'champ_inconnu'])
            ->assertDontSee('Montant du capital social');
    }

    public function test_preview_limit_and_validation_scope_are_explicit(): void
    {
        foreach ([80, 81, 161] as $count) {
            $fields = [];
            for ($i = 1; $i <= $count; $i++) {
                $fields[] = $this->field('inconnu', 'champ_'.$i, sprintf('VALEUR-%03d', $i));
            }
            $document = $this->document($fields);
            $response = $this->get(route('source-documents.show', $document))->assertOk()
                ->assertSee('VALEUR-080')->assertDontSee('VALEUR-081');
            if ($count > 80) {
                $response->assertSee("80 premiers champs affichés sur {$count} extraits. La validation porte sur l'ensemble des données extraites.", false);
            } else {
                $response->assertDontSee('premiers champs affichés');
            }
            $this->assertSame($fields, $document->extraction->fresh()->mapped_data);
        }
    }

    public function test_specialized_labels_are_rendered_in_their_own_table_context(): void
    {
        $cases = [
            ['dotations_amortissements', 'r0_c1', 'Type — Ligne 1'],
            ['dotations_amortissements', 'r1_c2', "Date d'entrée — Ligne 2"],
            ['dotations_amortissements', 'r2_c3', "Valeur à amortir — Prix d'acquisition — Ligne 3"],
            ['dotations_amortissements', 'r0_c4', 'Valeur à amortir — Valeur comptable après réévaluation — Ligne 1'],
            ['dotations_amortissements', 'r0_c5', 'Amortissements antérieurs — Ligne 1'],
            ['dotations_amortissements', 'r0_c6', 'Taux — Ligne 1'],
            ['dotations_amortissements', 'r0_c7', 'Durée — Ligne 1'],
            ['dotations_amortissements', 'r0_c8', "Amortissements normaux ou accélérés de l'exercice — Ligne 1"],
            ['dotations_amortissements', 'r0_c9', "Total des amortissements à la fin de l'exercice — Ligne 1"],
            ['dotations_amortissements', 'r0_c10', 'Observations — Ligne 1'],
            ['dotations_amortissements', 'total_c5', 'Total — Amortissements antérieurs'],
            ['passage_fiscal', 'reintegration_courante_0_label', 'Réintégration courante — Libellé — Ligne 1'],
            ['passage_fiscal', 'reports_deficitaires_total', 'Total des reports déficitaires'],
            ['affectation_resultats', 'ligne6_montantB', 'Report à nouveau — Affectation des résultats'],
            ['locations_baux', 'r0_c1', 'Nature du bien loué — Ligne 1'],
            ['methodes_evaluation', 'methode_0_6', "Actif immobilisé — Méthodes d'amortissements — Méthodes / Justifications"],
            ['methodes_evaluation', 'methode_1_1', 'Actif circulant hors trésorerie — Stocks — Méthodes / Justifications'],
            ['derogations', 'derogation_1_justification', "Dérogations — Méthodes d'évaluation — Justification"],
            ['changements_methodes', 'changement_1_0_nature', 'Changements — Règles de présentation — Nature — Ligne 1'],
            ['dotations_amortissements', 'r0_c99', 'r0_c99'],
            ['methodes_evaluation', 'methode_9_9', 'methode_9_9'],
            ['derogations', 'derogation_9_influence', 'derogation_9_influence'],
            ['changements_methodes', 'changement_9_0_nature', 'changement_9_0_nature'],
        ];
        foreach ($cases as [$table, $key, $label]) {
            $fields = [$this->field($table, $key, '001 234,50 <br> & valeur')];
            $document = $this->document($fields);
            $this->get(route('source-documents.show', $document))->assertOk()
                ->assertSeeInOrder([$label, \App\Presenters\SourceDocumentFieldPresenter::tableLabel($table), $fields[0]['valeur']]);
            $this->assertSame($fields, $document->extraction->fresh()->mapped_data);
        }
    }

    public function test_all_real_workbook_fields_have_labels_without_changing_values_or_duplicates(): void
    {
        $service = new \App\Services\DocumentExtractionService();
        $fields = (new \ReflectionMethod($service, 'mapDossierFiscalD3Soft'))->invoke(
            $service, base_path('docs/Dossier_Fiscal_D3Soft_2026_2 (2).xlsx'), 2026
        );
        $this->assertCount(161, $fields);
        $this->assertSame([
            'repartition_capital' => 16, 'dotations_amortissements' => 82,
            'passage_fiscal' => 9, 'affectation_resultats' => 7, 'locations_baux' => 5,
            'methodes_evaluation' => 30, 'derogations' => 6, 'changements_methodes' => 6,
        ], array_count_values(array_column($fields, 'tableau_code')));
        // Render every field in batches without changing the production preview limit.
        foreach (array_chunk($fields, 80) as $batch) {
            $document = $this->document($batch);
            $response = $this->get(route('source-documents.show', $document))->assertOk();
            foreach ($batch as $field) {
                $label = \App\Presenters\SourceDocumentFieldPresenter::fieldLabel($field['tableau_code'], $field['cle']);
                $this->assertNotSame($field['cle'], $label);
                $response->assertSee($label)->assertSee($field['valeur']);
            }
            $this->assertSame($batch, $document->extraction->fresh()->mapped_data);
        }
    }

    private function field(string $table, string $key, string $value): array
    {
        return ['tableau_code' => $table, 'cle' => $key, 'valeur' => $value, 'ligne' => null, 'colonne' => null];
    }

    private function document(array $fields): SourceDocument
    {
        $user = User::factory()->create();
        $societe = Societe::create(['user_id' => $user->id, 'nom_societe' => 'Société de test']);
        $this->actingAs($user);
        $document = SourceDocument::create([
            'user_id' => $user->id, 'societe_id' => $societe->id, 'exercice' => 2026,
            'document_type' => 'dossier_fiscal_complet', 'tableau_code' => 'multi_tableaux',
            'original_name' => 'dossier.xlsx', 'stored_path' => 'test/dossier.xlsx',
            'size' => 1, 'status' => SourceDocument::STATUS_NEEDS_VALIDATION,
        ]);
        $document->extraction()->create([
            'mapped_data' => $fields, 'raw_data' => [], 'errors' => [],
            'status' => SourceDocument::STATUS_NEEDS_VALIDATION,
        ]);

        return $document;
    }
}
