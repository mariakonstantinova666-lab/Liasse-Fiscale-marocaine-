<?php

namespace Tests\Feature;

use App\Models\BalanceItem;
use App\Models\Societe;
use App\Models\User;
use App\Services\AccountingAnomalyFeatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class AccountingAnomalyFeatureServiceTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        $user = User::factory()->create();
        return [$user->id, Societe::create(['user_id' => $user->id, 'nom_societe' => 'Feature test'])->id];
    }

    private function row(array $context, int $year, string $account, float $debit, float $credit = 0): void
    {
        BalanceItem::create(['user_id' => $context[0], 'societe_id' => $context[1], 'exercice' => $year,
            'compte' => $account, 'libelle' => 'Account '.$account, 'solde_debiteur' => $debit, 'solde_crediteur' => $credit]);
    }

    private function build(array $context): array
    {
        return (new AccountingAnomalyFeatureService)->buildTransition(...[...$context, 2024, 2025]);
    }

    public function test_exact_math_matching_five_features_and_no_writes(): void
    {
        $c = $this->context();
        foreach ([[2024, '6111', 120, 20], [2024, '6112', 100, 0], [2024, '7111', 0, 200],
            [2025, '7111', 0, 200], [2025, '6112', 200, 0], [2025, '6111', 220, 20]] as $r) {
            $this->row($c, ...$r);
        }
        $before = BalanceItem::orderBy('id')->get()->toArray();
        $rows = $this->build($c);
        $this->assertCount(3, $rows);
        $r = $rows[0];
        $this->assertSame('6111', $r['account']);
        $this->assertSame(100.0, $r['previous_balance']);
        $this->assertSame(200.0, $r['current_balance']);
        $this->assertSame(100.0, $r['absolute_change']);
        $this->assertSame(1.0, $r['relative_change']);
        $expected = ['symmetric_change' => 2 / 3, 'class_scaled_change' => .5,
            'share_class_current' => .5, 'share_class_change' => 0.0, 'share_total_change' => 1 / 12];
        $this->assertSame(array_keys($expected), array_keys($r['features']));
        foreach ($expected as $key => $value) {
            $this->assertEqualsWithDelta($value, $r['features'][$key], 1e-12);
        }
        foreach ($rows as $observation) {
            foreach ($observation['features'] as $value) {
                $this->assertTrue(is_finite($value));
            }
            $this->assertLessThanOrEqual(2.0, abs($observation['features']['symmetric_change']));
        }
        $this->assertSame($before, BalanceItem::orderBy('id')->get()->toArray());
        $this->assertSame($rows, $this->build($c));
    }

    public function test_zero_denominators_and_strict_sign_changes(): void
    {
        foreach ([[0, 0, false], [0, 10, false], [0, -10, false], [10, 0, false], [-10, 0, false], [10, -10, true], [-10, 10, true]] as [$p, $n, $sign]) {
            $c = $this->context();
            $this->row($c, 2024, '6111', max($p, 0), max(-$p, 0));
            $this->row($c, 2025, '6111', max($n, 0), max(-$n, 0));
            $r = $this->build($c)[0];
            $this->assertSame($sign, $r['sign_change']);
            $this->assertSame($p == 0, $r['previous_zero']);
            $this->assertSame($n == 0, $r['current_zero']);
            $this->assertSame($p == 0 ? null : (float) (($n - $p) / abs($p)), $r['relative_change']);
            $this->assertSame($p == 0, $r['zero_denominators']['class_previous']);
            $this->assertSame($n == 0, $r['zero_denominators']['class_current']);
            $this->assertSame($p == 0, $r['zero_denominators']['total_previous']);
            $this->assertSame($n == 0, $r['zero_denominators']['total_current']);
            $expected = [($n == 0 && $p == 0) ? 0.0 : 2 * ($n - $p) / (abs($n) + abs($p)),
                $p == 0 ? 0.0 : ($n - $p) / abs($p), $n == 0 ? 0.0 : 1.0,
                ($n == 0 ? 0.0 : 1.0) - ($p == 0 ? 0.0 : 1.0)];
            foreach (array_values($r['features']) as $i => $value) {
                $this->assertTrue(is_finite($value));
                $this->assertEqualsWithDelta($expected[min($i, 3)], $value, 1e-12);
            }
        }
    }

    public function test_isolation_by_user_society_and_explicit_years(): void
    {
        $c = $this->context();
        $other = $this->context();
        $sameUser = [$c[0], Societe::create(['user_id' => $c[0], 'nom_societe' => 'Other'])->id];
        foreach ([$c, $other, $sameUser, [$other[0], $c[1]]] as $context) {
            foreach ([2024, 2025, 2026] as $year) {
                $this->row($context, $year, '6111', $context === $c ? 10 : 999);
            }
        }
        session(['annee_exercice' => 2030]);
        $r = $this->build($c)[0];
        $this->assertSame(10.0, $r['current_balance']);
        $this->assertSame($c[0], $r['user_id']);
        $this->assertSame($c[1], $r['societe_id']);
        $this->assertSame(2024, $r['previous_year']);
        $this->assertSame(2025, $r['current_year']);
        $this->assertSame(2030, session('annee_exercice'));
    }

    public function test_missing_annual_balances_are_rejected(): void
    {
        foreach ([null, 2024, 2025] as $year) {
            $c = $this->context();
            if ($year !== null) { $this->row($c, $year, '6111', 10); }
            try { $this->build($c); $this->fail('Missing balance accepted'); }
            catch (RuntimeException $e) { $this->assertStringContainsString('Both annual balances', $e->getMessage()); }
        }
    }

    public function test_missing_accounts_in_either_year_are_rejected(): void
    {
        foreach ([2024, 2025] as $year) {
            $c = $this->context();
            $this->row($c, 2024, '6111', 10); $this->row($c, 2025, '6111', 10);
            $this->row($c, $year, '6112', 10);
            try { $this->build($c); $this->fail('Missing account accepted'); }
            catch (RuntimeException $e) { $this->assertStringContainsString('Account sets differ', $e->getMessage()); }
        }
    }

    public function test_duplicates_in_either_year_are_rejected(): void
    {
        foreach ([2024, 2025] as $year) {
            $c = $this->context();
            $this->row($c, 2024, '6111', 10); $this->row($c, 2025, '6111', 10); $this->row($c, $year, '6111', 20);
            try { $this->build($c); $this->fail('Duplicate accepted'); }
            catch (RuntimeException $e) { $this->assertStringContainsString('Duplicate account', $e->getMessage()); }
        }
    }

    public function test_foreign_society_is_rejected(): void
    {
        $c = $this->context(); $other = $this->context();
        $this->expectException(RuntimeException::class);
        $this->build([$c[0], $other[1]]);
    }

    public function test_invalid_years_are_rejected(): void
    {
        $c = $this->context();
        foreach ([[2025, 2024], [2024, 2024], [2024, 2026], [999, 1000], [9999, 10000]] as [$p, $n]) {
            try { (new AccountingAnomalyFeatureService)->buildTransition(...[...$c, $p, $n]); $this->fail('Invalid years accepted'); }
            catch (InvalidArgumentException $e) { $this->assertStringContainsString('consecutive', $e->getMessage()); }
        }
    }
}
