<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\BalanceItem;
use App\Models\LiasseData;
use App\Models\Societe;
use App\Services\EdiGenerationException;
use App\Services\EdiXmlGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EdiXmlGeneratorServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_edi_generation_is_blocked_when_coherence_engine_returns_blocking_errors(): void
    {
        $user = User::factory()->create();

        $this->expectException(EdiGenerationException::class);

        app(EdiXmlGeneratorService::class)->generate($user->id, 2026);
    }

    public function test_edi_generation_creates_dgi_simpl_is_xml_when_controls_pass(): void
    {
        config(['edi.required_complete_tables' => [
            'passage_fiscal' => 'Passage fiscal',
            'repartition_capital' => 'Repartition du capital',
            'affectation_resultats' => 'Affectation des resultats',
        ]]);

        $user = User::factory()->create();
        $societe = Societe::create([
            'user_id' => $user->id,
            'nom_societe' => 'D3 Soft SARL AU',
            'if' => '12345678',
            'ice' => '001234567000089',
            'rc' => '99999',
            'cnss' => '8912345',
            'patente' => '34567890',
            'adresse' => 'Avenue de la Paix, Tanger',
        ]);

        foreach ([
            ['11110000', 'Capital social', 0, 1000],
            ['34210000', 'Clients', 1300, 0],
            ['61250000', 'Achats consommes', 200, 0],
            ['71240000', 'Prestations de services', 0, 500],
        ] as [$compte, $libelle, $debit, $credit]) {
            BalanceItem::create([
                'user_id' => $user->id,
                'societe_id' => $societe->id,
                'compte' => $compte,
                'libelle' => $libelle,
                'solde_debiteur' => $debit,
                'solde_crediteur' => $credit,
                'exercice' => 2026,
            ]);
        }

        foreach ([
            ['repartition_capital', 'montant_capital', '1000'],
            ['repartition_capital', 'total_c10', '1000'],
            ['affectation_resultats', 'ligne4_montantA', '300'],
            ['affectation_resultats', 'ligne6_montantB', '300'],
            ['affectation_resultats', 'total_A', '300'],
            ['affectation_resultats', 'total_B', '300'],
            ['passage_fiscal', 'reintegrations_courantes_total', '0'],
            ['passage_fiscal', 'reintegrations_non_courantes_total', '0'],
            ['passage_fiscal', 'deductions_courantes_total', '0'],
            ['passage_fiscal', 'deductions_non_courantes_total', '0'],
        ] as [$tableau, $cle, $valeur]) {
            LiasseData::create([
                'user_id' => $user->id,
                'exercice' => 2026,
                'tableau_code' => $tableau,
                'cle' => $cle,
                'valeur' => $valeur,
            ]);
        }

        $result = app(EdiXmlGeneratorService::class)->generate($user->id, 2026);
        $xml = simplexml_load_file($result['path']);

        $this->assertSame('Liasse', $xml->getName());
        $this->assertSame('7', (string) $xml->modele->id);
        $this->assertSame('D3 Soft SARL AU', (string) $xml->societe->raisonSociale);
        $this->assertSame('12345678', (string) $xml->societe->identifiantFiscal);
        $this->assertSame('01/01/2026', (string) $xml->societe->exerciceDu);
        $this->assertSame('300.00', (string) $xml->resultatFiscal);
        $this->assertGreaterThan(0, $result['mapped_values']);
        $this->assertStringContainsString('<codeEdi>817</codeEdi>', file_get_contents($result['path']));
        $this->assertStringContainsString('<codeEdi>481</codeEdi>', file_get_contents($result['path']));
    }

    public function test_edi_generation_is_blocked_when_complete_table_coverage_is_missing(): void
    {
        $user = User::factory()->create();
        $societe = Societe::create([
            'user_id' => $user->id,
            'nom_societe' => 'D3 Soft SARL AU',
            'if' => '12345678',
        ]);

        foreach ([
            ['11110000', 'Capital social', 0, 1000],
            ['34210000', 'Clients', 1300, 0],
            ['61250000', 'Achats consommes', 200, 0],
            ['71240000', 'Prestations de services', 0, 500],
        ] as [$compte, $libelle, $debit, $credit]) {
            BalanceItem::create([
                'user_id' => $user->id,
                'societe_id' => $societe->id,
                'compte' => $compte,
                'libelle' => $libelle,
                'solde_debiteur' => $debit,
                'solde_crediteur' => $credit,
                'exercice' => 2026,
            ]);
        }

        foreach ([
            ['repartition_capital', 'montant_capital', '1000'],
            ['repartition_capital', 'total_c10', '1000'],
            ['affectation_resultats', 'ligne4_montantA', '300'],
            ['affectation_resultats', 'ligne6_montantB', '300'],
            ['affectation_resultats', 'total_A', '300'],
            ['affectation_resultats', 'total_B', '300'],
            ['passage_fiscal', 'reintegrations_courantes_total', '0'],
            ['passage_fiscal', 'reintegrations_non_courantes_total', '0'],
        ] as [$tableau, $cle, $valeur]) {
            LiasseData::create([
                'user_id' => $user->id,
                'exercice' => 2026,
                'tableau_code' => $tableau,
                'cle' => $cle,
                'valeur' => $valeur,
            ]);
        }

        try {
            app(EdiXmlGeneratorService::class)->generate($user->id, 2026);
            $this->fail('La generation aurait du etre bloquee par la couverture incomplete.');
        } catch (EdiGenerationException $exception) {
            $this->assertSame('EDI_INCOMPLETE_TABLE_COVERAGE', $exception->blockingErrors()[0]['id']);
        }
    }
}
