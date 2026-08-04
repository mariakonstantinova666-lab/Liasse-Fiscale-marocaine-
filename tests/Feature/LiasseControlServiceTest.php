<?php

namespace Tests\Feature;

use App\Models\BalanceItem;
use App\Models\LiasseData;
use App\Services\LiasseControlService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class LiasseControlServiceTest extends TestCase
{
    private function item(string $compte, float $debit = 0, float $credit = 0): BalanceItem
    {
        return new BalanceItem([
            'compte' => $compte,
            'libelle' => $compte,
            'solde_debiteur' => $debit,
            'solde_crediteur' => $credit,
            'exercice' => 2026,
        ]);
    }

    private function verifier(Collection $items): Collection
    {
        return collect((new LiasseControlService)->verifier($items));
    }

    public function test_coherent_balance_has_no_blocking_control_error(): void
    {
        $controles = $this->verifier(collect([
            $this->item('11110000', credit: 1000),
            $this->item('34210000', debit: 1300),
            $this->item('61250000', debit: 200),
            $this->item('71240000', credit: 500),
        ]));

        $this->assertSame(0, $controles->where('bloquant', true)->where('ok', false)->count());
        $this->assertTrue($controles->firstWhere('titre', 'Équilibre de la balance (Total débit = Total crédit)')['ok']);
        $this->assertTrue($controles->firstWhere('titre', 'Cohérence du résultat (CPC / Bilan)')['ok']);
    }

    public function test_zero_119_account_does_not_create_false_result_warning(): void
    {
        $controles = $this->verifier(collect([
            $this->item('11110000', credit: 1000),
            $this->item('11990000'),
            $this->item('34210000', debit: 1300),
            $this->item('61250000', debit: 200),
            $this->item('71240000', credit: 500),
        ]));

        $controle119 = $controles->firstWhere('titre', 'Résultat net comptabilisé (compte 119)');

        $this->assertNotNull($controle119);
        $this->assertTrue($controle119['ok']);
        $this->assertFalse($controle119['bloquant']);
    }

    public function test_quality_controls_detect_negative_amounts_and_double_sided_lines(): void
    {
        $controles = $this->verifier(collect([
            $this->item('34210000', debit: 100, credit: 20),
            $this->item('61250000', debit: -10),
            $this->item('71240000', credit: 70),
        ]));

        $this->assertFalse($controles->firstWhere('titre', 'Montants négatifs dans la balance')['ok']);
        $this->assertFalse($controles->firstWhere('titre', 'Débit et crédit simultanés sur une même ligne')['ok']);
    }

    public function test_controls_return_edi_ready_metadata(): void
    {
        $controles = $this->verifier(collect([
            $this->item('34210000', debit: 100),
            $this->item('71240000', credit: 100),
        ]));

        $controle = $controles->firstWhere('id', 'BALANCE_DEBIT_CREDIT_EQUAL');

        $this->assertNotNull($controle);
        $this->assertArrayHasKey('severity', $controle);
        $this->assertArrayHasKey('tableau', $controle);
        $this->assertArrayHasKey('rubrique', $controle);
        $this->assertArrayHasKey('regle', $controle);
        $this->assertArrayHasKey('suggestion', $controle);
    }

    public function test_full_liasse_controls_detect_t14_total_mismatch(): void
    {
        $items = collect([
            $this->item('11110000', credit: 1000),
            $this->item('34210000', debit: 1300),
            $this->item('61250000', debit: 200),
            $this->item('71240000', credit: 500),
        ]);
        $liasseData = collect([
            new LiasseData(['tableau_code' => 'repartition_capital', 'cle' => 'montant_capital', 'valeur' => '1000']),
            new LiasseData(['tableau_code' => 'affectation_resultats', 'cle' => 'total_A', 'valeur' => '100']),
            new LiasseData(['tableau_code' => 'affectation_resultats', 'cle' => 'total_B', 'valeur' => '90']),
            new LiasseData(['tableau_code' => 'passage_fiscal', 'cle' => 'reintegrations_courantes_total', 'valeur' => '0']),
            new LiasseData(['tableau_code' => 'passage_fiscal', 'cle' => 'reintegrations_non_courantes_total', 'valeur' => '0']),
        ]);

        $controles = collect((new LiasseControlService)->verifierLiasse($items, $liasseData, collect()));

        $controle = $controles->firstWhere('id', 'T14_TOTAL_A_EQUALS_B');

        $this->assertNotNull($controle);
        $this->assertFalse($controle['ok']);
        $this->assertTrue($controle['bloquant']);
    }

    public function test_text_columns_are_not_reported_as_invalid_amounts(): void
    {
        $items = collect([
            $this->item('11110000', credit: 1000),
            $this->item('34210000', debit: 1300),
            $this->item('61250000', debit: 200),
            $this->item('71240000', credit: 500),
        ]);
        $liasseData = collect([
            new LiasseData(['tableau_code' => 'repartition_capital', 'cle' => 'montant_capital', 'valeur' => '1000']),
            new LiasseData(['tableau_code' => 'repartition_capital', 'cle' => 'r0_c4', 'valeur' => 'CD98452']),
            new LiasseData(['tableau_code' => 'dotations_amortissements', 'cle' => 'r0_c10', 'valeur' => 'Immatriculation RC']),
            new LiasseData(['tableau_code' => 'locations_baux', 'cle' => 'r0_c3', 'valeur' => 'TAZI Abdelkader']),
            new LiasseData(['tableau_code' => 'locations_baux', 'cle' => 'r0_c5', 'valeur' => '12 Rue Mohammed V, Tanger']),
            new LiasseData(['tableau_code' => 'locations_baux', 'cle' => 'r0_c9', 'valeur' => '01/01/2024']),
            new LiasseData(['tableau_code' => 'locations_baux', 'cle' => 'r0_c12', 'valeur' => 'X']),
            new LiasseData(['tableau_code' => 'affectation_resultats', 'cle' => 'total_A', 'valeur' => '100']),
            new LiasseData(['tableau_code' => 'affectation_resultats', 'cle' => 'total_B', 'valeur' => '100']),
            new LiasseData(['tableau_code' => 'passage_fiscal', 'cle' => 'reintegrations_courantes_total', 'valeur' => '0']),
            new LiasseData(['tableau_code' => 'passage_fiscal', 'cle' => 'reintegrations_non_courantes_total', 'valeur' => '0']),
        ]);

        $controles = collect((new LiasseControlService)->verifierLiasse($items, $liasseData, collect()));

        $this->assertTrue($controles->firstWhere('id', 'LIASSE_FIELD_FORMATS')['ok']);
    }
}
