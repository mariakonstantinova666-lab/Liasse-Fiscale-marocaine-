<?php

namespace Tests\Feature;

use App\Http\Controllers\LiasseController;
use App\Models\BalanceItem;
use App\Services\BalanceService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class LiasseCalculationTest extends TestCase
{
    private function item(string $compte, float $debit = 0, float $credit = 0): BalanceItem
    {
        return new BalanceItem([
            'compte' => $compte,
            'libelle' => $compte,
            'solde_debiteur' => $debit,
            'solde_crediteur' => $credit,
            'exercice' => 2017,
        ]);
    }

    private function invoke(string $method, array $arguments): mixed
    {
        $reflection = new ReflectionMethod(LiasseController::class, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs(new LiasseController, $arguments);
    }

    public function test_cpc_respects_account_sign_and_previous_period_operations(): void
    {
        $n = collect([
            $this->item('71240000', credit: 264857.67),
            $this->item('61612000', debit: 4816),
            $this->item('61680000', debit: 9600),
        ]);
        $n1 = collect([
            $this->item('71240000', credit: 238371.90),
            $this->item('61612000', debit: 4329),
        ]);

        $produit = $this->invoke('calculateRow', [$n, $n1, '712']);
        $charge = $this->invoke('calculateRow', [$n, $n1, '616']);

        $this->assertEqualsWithDelta(264857.67, $produit->col3, 0.001);
        $this->assertEqualsWithDelta(238371.90, $produit->col4, 0.001);
        $this->assertEqualsWithDelta(4816, $charge->col1, 0.001);
        $this->assertEqualsWithDelta(9600, $charge->col2, 0.001);
        $this->assertEqualsWithDelta(14416, $charge->col3, 0.001);
    }

    public function test_amortissements_use_n_minus_one_opening_and_n_closing_balances(): void
    {
        $n = collect([
            $this->item('28356000', credit: 87403.12),
            $this->item('61935000', debit: 7815.47),
        ]);
        $n1 = collect([$this->item('28356000', credit: 79587.65)]);

        $line = $this->invoke('calculerLigneAmortissement', [$n, $n1, '2835', '61935']);

        $this->assertEqualsWithDelta(79587.65, $line->col1, 0.001);
        $this->assertEqualsWithDelta(7815.47, $line->col2, 0.001);
        $this->assertEqualsWithDelta(0, $line->col3, 0.001);
        $this->assertEqualsWithDelta(87403.12, $line->col4, 0.001);
    }

    public function test_stock_line_uses_n_as_final_and_n_minus_one_as_initial(): void
    {
        $n = collect([$this->item('31510000', debit: 471035.17)]);
        $n1 = collect([$this->item('31510000', debit: 401035.17)]);

        $line = $this->invoke('calculerLigneStock', [$n, $n1, '315', '3915']);

        $this->assertEqualsWithDelta(471035.17, $line->final_net, 0.001);
        $this->assertEqualsWithDelta(401035.17, $line->initial_net, 0.001);
        $this->assertEqualsWithDelta(70000, $line->variation, 0.001);
    }

    public function test_tva_is_derived_from_both_balances_without_hard_coded_values(): void
    {
        $n = collect([
            $this->item('44550000', credit: 120708.12),
            $this->item('34550000', debit: 10904.63),
            $this->item('34551000', debit: 6370),
        ]);
        $n1 = collect([
            $this->item('44550000', credit: 108637.31),
            $this->item('34550000', debit: 9814.17),
            $this->item('34551000', debit: 5733),
        ]);

        Auth::shouldReceive('id')->once()->andReturn(1);
        session(['annee_exercice' => 2017]);
        $service = Mockery::mock(BalanceService::class);
        $service->shouldReceive('lignesAvecPrecedent')->with(1, 2017)->andReturn([$n, $n1]);

        $view = (new LiasseController)->tva($service);
        $rows = $view->getData()['tvaRows'];

        $this->assertEqualsWithDelta(108637.31, $rows[0]['values']->debut, 0.001);
        $this->assertEqualsWithDelta(120708.12, $rows[0]['values']->fin, 0.001);
        $this->assertEqualsWithDelta(15547.17, $rows[1]['values']->debut, 0.001);
        $this->assertEqualsWithDelta(17274.63, $rows[1]['values']->fin, 0.001);
    }

    public function test_bilan_passif_populates_current_and_previous_exercises(): void
    {
        $n = collect([
            $this->item('11110000', credit: 200000),
            $this->item('11690000', debit: 311644.68),
            $this->item('71240000', credit: 10000),
            $this->item('61250000', debit: 12665.62),
            $this->item('44110000', credit: 45342.32),
        ]);
        $n1 = collect([
            $this->item('11110000', credit: 200000),
            $this->item('11690000', debit: 273644.68),
            $this->item('71240000', credit: 10000),
            $this->item('61250000', debit: 2513.74),
            $this->item('44110000', credit: 40808.09),
        ]);

        Auth::shouldReceive('id')->once()->andReturn(1);
        session(['annee_exercice' => 2017]);
        $service = Mockery::mock(BalanceService::class);
        $service->shouldReceive('lignesAvecPrecedent')->with(1, 2017)->andReturn([$n, $n1]);

        $view = (new LiasseController)->bilanPassif($service);
        $data = $view->getData()['data'];

        $this->assertStringContainsString('BILAN - PASSIF', $view->render());
        $this->assertEqualsWithDelta(-2665.62, $data['CAPITAUX PROPRES']["Résultat net de l'exercice (2)"]->montant, 0.001);
        $this->assertEqualsWithDelta(7486.26, $data['CAPITAUX PROPRES']["Résultat net de l'exercice (2)"]->montant_prec, 0.001);
        $this->assertEqualsWithDelta(40808.09, $data['DETTES DU PASSIF CIRCULANT ( f )']['Fournisseurs et comptes rattachés']->montant_prec, 0.001);
    }

    public function test_financing_uses_reference_balance_masses_and_not_the_119_account(): void
    {
        $n = collect([
            $this->item('11110000', credit: 200000),
            $this->item('11690000', debit: 311644.68),
            $this->item('11990000', debit: 999999),
            $this->item('71240000', credit: 10000),
            $this->item('61250000', debit: 12665.62),
            $this->item('31510000', debit: 471035.17),
            $this->item('44110000', credit: 45342.32),
        ]);
        $n1 = collect([
            $this->item('11110000', credit: 200000),
            $this->item('11690000', debit: 273644.68),
            $this->item('11990000', debit: 38000),
            $this->item('71240000', credit: 10000),
            $this->item('61250000', debit: 2513.74),
            $this->item('31510000', debit: 401035.17),
            $this->item('44110000', credit: 40808.09),
        ]);

        Auth::shouldReceive('id')->once()->andReturn(1);
        session(['annee_exercice' => 2017]);
        $service = Mockery::mock(BalanceService::class);
        $service->shouldReceive('lignesAvecPrecedent')->with(1, 2017)->andReturn([$n, $n1]);

        $view = (new LiasseController)->tableauFinancement($service);
        $rows = $view->getData()['synthese'];

        $this->assertEqualsWithDelta(-114310.30, $rows[0]['n'], 0.001);
        $this->assertEqualsWithDelta(-66158.42, $rows[0]['p'], 0.001);
        $this->assertEqualsWithDelta(48151.88, $rows[0]['emploi'], 0.001);
    }
}
