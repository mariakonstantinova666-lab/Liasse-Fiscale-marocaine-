<?php

namespace Tests\Feature;

use App\Services\AccountingAnomalyFeatureService;
use App\Services\AccountingAnomalyScenarioService;
use RuntimeException;
use Tests\TestCase;

class AccountingAnomalyScenarioServiceTest extends TestCase
{
    private function source(): array
    {
        $rows = [];
        foreach ([2024, 2025] as $year) {
            foreach ([['6131', 100, 0], ['4411', 0, 100], ['7121', 0, 200], ['3421', 200, 0], ['1191', 0, 0]] as [$account, $debit, $credit]) {
                $rows[] = ['user_id' => 1, 'societe_id' => 2, 'exercice' => $year, 'compte' => $account,
                    'libelle' => 'Account '.$account, 'solde_debiteur' => $debit, 'solde_crediteur' => $credit];
            }
        }
        return $rows;
    }

    private function generate(array $source, int $seed = 42): array
    {
        return (new AccountingAnomalyScenarioService(new AccountingAnomalyFeatureService))->generate($source, 1, 2, 2024, 2025, $seed);
    }

    public function test_reproducibility_integrity_labels_invariants_and_no_database_access(): void
    {
        // Stronger than no writes: any Eloquent query in this pure path fails the test.
        $old = \Illuminate\Database\Eloquent\Model::getConnectionResolver();
        $resolver = \Mockery::mock(\Illuminate\Database\ConnectionResolverInterface::class);
        $resolver->shouldNotReceive('connection');
        \Illuminate\Database\Eloquent\Model::setConnectionResolver($resolver);
        try {
            $source = $this->source(); $before = $source;
            $batch = $this->generate($source);
            $this->assertSame($before, $source);
            $this->assertSame($batch, $this->generate($source));
            $this->assertSame(hash('sha256', serialize($batch)), hash('sha256', serialize($this->generate($source))));
            $this->assertNotSame($batch, $this->generate($source, 43));
            $this->assertNotSame(array_column($batch['scenarios'], 'snapshot'), array_column($this->generate($source, 43)['scenarios'], 'snapshot'));
            $this->assertSame('original', $batch['original']['scenario_type']);
            $this->assertNull($batch['original']['synthetic_label']);
            $this->assertCount(20, $batch['scenarios']);
            foreach ($batch['scenarios'] as $i => $scenario) {
                $label = $i < 10 ? 0 : 1;
                $this->assertSame($label, $scenario['synthetic_label']);
                $this->assertSame($i < 10 ? 'synthetic_near_normal' : 'synthetic_anomaly', $scenario['scenario_type']);
                $this->assertGreaterThanOrEqual($i < 10 ? .01 : .5, $scenario['magnitude']);
                $this->assertLessThanOrEqual($i < 10 ? .05 : 1, $scenario['magnitude']);
                $this->assertCount(2, $scenario['modified_accounts']);
                $sum = 0; $deltas = [];
                foreach ($scenario['snapshot'] as $r) {
                    $original = array_values(array_filter($source, fn ($x) => $x['compte'] === $r['compte'] && $x['exercice'] === $r['exercice']))[0];
                    foreach (['user_id', 'societe_id', 'exercice', 'compte', 'libelle'] as $key) { $this->assertSame($original[$key], $r[$key]); }
                    $net = (int) round(100 * ($r['solde_debiteur'] - $r['solde_crediteur']));
                    if ($r['exercice'] === 2024 || str_starts_with($r['compte'], '119')) { $this->assertSame($original, $r); }
                    if ($r['exercice'] === 2025) {
                        $sum += $net;
                        $delta = $net - (int) round(100 * ($original['solde_debiteur'] - $original['solde_crediteur']));
                        if ($delta !== 0) { $deltas[$r['compte']] = $delta; }
                    }
                }
                $this->assertSame(0, $sum);
                $this->assertCount(2, $deltas);
                $this->assertSame(0, array_sum($deltas));
                $this->assertEqualsWithDelta($scenario['amount'], abs($deltas[$scenario['targeted_accounts'][0]]) / 100, 1e-12);
                $this->assertCount(5, $scenario['observations']);
                $plain = (new AccountingAnomalyFeatureService)->buildFromSnapshot(1, 2, 2024, 2025, $scenario['snapshot']);
                foreach ($scenario['observations'] as $j => $o) {
                    $this->assertSame($plain[$j]['features'], $o['features']);
                    $this->assertCount(5, $o['features']);
                    foreach ($o['features'] as $v) { $this->assertTrue(is_finite($v)); }
                    $target = $o['account'] === $scenario['targeted_accounts'][0];
                    $counter = $o['account'] === $scenario['counterpart_accounts'][0];
                    $this->assertSame($target ? $label : 0, $o['synthetic_label']);
                    $this->assertSame($target && $label === 1, $o['targeted_anomaly']);
                    $this->assertSame($counter, $o['counterpart_effect']);
                    $this->assertSame($counter, $o['affected_by_counterpart']);
                    $this->assertSame($o['account'], $o['original_account']);
                }
            }
        } finally {
            if ($old !== null) { \Illuminate\Database\Eloquent\Model::setConnectionResolver($old); }
            else { \Illuminate\Database\Eloquent\Model::unsetConnectionResolver(); }
        }
    }

    public function test_invalid_snapshots_are_explicitly_rejected(): void
    {
        foreach (['unbalanced', 'closing', 'foreign', 'future', 'duplicate', 'missing', 'nonfinite', 'no_pair'] as $case) {
            $s = $this->source();
            switch ($case) {
                case 'unbalanced': $s[0]['solde_debiteur'] = 101; break;
                case 'closing': $s[4]['solde_debiteur'] = 1; break;
                case 'foreign': $s[0]['user_id'] = 8; break;
                case 'future': $s[0]['exercice'] = 2026; break;
                case 'duplicate': $s[] = $s[0]; break;
                case 'missing': array_pop($s); break;
                case 'nonfinite': $s[0]['solde_debiteur'] = INF; break;
                case 'no_pair': foreach ($s as &$r) { $r['compte'] = str_replace(['613', '712'], ['614', '718'], $r['compte']); } unset($r); break;
            }
            try { $this->generate($s); $this->fail('Invalid snapshot accepted: '.$case); }
            catch (RuntimeException $e) { $this->assertNotEmpty($e->getMessage()); }
        }
    }

    public function test_2026_transition_is_not_permitted_in_phase_two(): void
    {
        $this->expectException(RuntimeException::class);
        (new AccountingAnomalyScenarioService(new AccountingAnomalyFeatureService))->generate($this->source(), 1, 2, 2025, 2026);
    }

    public function test_each_enriched_family_with_bounded_settlements(): void
    {
        $source = $this->source();
        foreach ([2024, 2025] as $year) {
            foreach ([['5141', 100, 0], ['1111', 0, 200], ['2351', 100, 0]] as [$account, $d, $c]) {
                $source[] = ['user_id' => 1, 'societe_id' => 2, 'exercice' => $year, 'compte' => $account,
                    'libelle' => 'Account '.$account, 'solde_debiteur' => $d, 'solde_crediteur' => $c];
            }
        }
        $before = $source;
        $service = new AccountingAnomalyScenarioService(new AccountingAnomalyFeatureService);
        foreach (['supplier_payment', 'customer_collection', 'equipment_acquisition'] as $family) {
            $batch = $service->generate($source, 1, 2, 2024, 2025, 42, 6, 6, [$family]);
            $this->assertSame($batch, $service->generate($source, 1, 2, 2024, 2025, 42, 6, 6, [$family]));
            foreach ($batch['scenarios'] as $index => $s) {
                $this->assertSame($family, $s['operation_type']);
                $this->assertSame($index < 6 ? 0 : 1, $s['synthetic_label']);
                $sum = 0;
                foreach ($s['snapshot'] as $r) {
                    if ($r['exercice'] !== 2025) { continue; }
                    $sum += (int) round(100 * ($r['solde_debiteur'] - $r['solde_crediteur']));
                    $this->assertGreaterThanOrEqual(0, $r['solde_debiteur']);
                    $this->assertGreaterThanOrEqual(0, $r['solde_crediteur']);
                    if ($family === 'supplier_payment' && $r['compte'] === '5141') { $this->assertGreaterThan(0, $r['solde_debiteur']); $this->assertEquals(0, $r['solde_crediteur']); }
                    if ($family === 'customer_collection' && $r['compte'] === '3421') { $this->assertGreaterThan(0, $r['solde_debiteur']); $this->assertEquals(0, $r['solde_crediteur']); }
                }
                $this->assertSame(0, $sum);
                foreach ($s['observations'] as $o) {
                    $this->assertCount(5, $o['features']);
                    foreach ($o['features'] as $v) { $this->assertTrue(is_finite($v)); }
                    $this->assertSame($o['targeted_anomaly'] ? 1 : 0, $o['synthetic_label']);
                }
            }
        }
        $this->assertSame($before, $source);
    }
}
