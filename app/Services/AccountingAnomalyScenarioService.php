<?php

namespace App\Services;

use Random\Engine\Mt19937;
use Random\Randomizer;
use RuntimeException;

/** In-memory, pre-closing experiments. Percentages are simulation assumptions, not fiscal thresholds. */
class AccountingAnomalyScenarioService
{
    public function __construct(private AccountingAnomalyFeatureService $features)
    {
    }

    public function generate(array $source, int $userId, int $societeId, int $previousYear, int $currentYear, int $seed = 42, int $nearCount = 10, int $anomalyCount = 10, array $allowedOperations = ['expense_accrual', 'revenue_on_credit']): array
    {
        if ($previousYear !== 2024 || $currentYear !== 2025 || $nearCount < 0 || $anomalyCount < 0 || $nearCount + $anomalyCount > 100) {
            throw new RuntimeException('Phase 2 requires 2024 -> 2025 and at most 100 scenarios.');
        }
        $originalFeatures = $this->features->buildFromSnapshot($userId, $societeId, $previousYear, $currentYear, $source);
        $this->validateAccounting($source, $previousYear);
        $this->validateAccounting($source, $currentYear);
        usort($source, fn ($a, $b) => strcmp($a['exercice'].':'.$a['compte'], $b['exercice'].':'.$b['compte']));
        $operations = [];
        $definitions = [['expense_accrual', '613', '441'], ['revenue_on_credit', '712', '342'],
            ['supplier_payment', '514', '441'], ['customer_collection', '342', '514'], ['equipment_acquisition', '235', '441']];
        if ($allowedOperations === [] || array_diff($allowedOperations, array_column($definitions, 0)) !== []) {
            throw new RuntimeException('Unknown or empty operation selection.');
        }
        foreach ($definitions as [$type, $targetPrefix, $counterPrefix]) {
            if (!in_array($type, $allowedOperations, true)) { continue; }
            $targets = $counterparts = [];
            foreach ($source as $i => $row) {
                if ((int) $row['exercice'] !== $currentYear) { continue; }
                $net = $this->cents($row['solde_debiteur']) - $this->cents($row['solde_crediteur']);
                if (str_starts_with($row['compte'], $targetPrefix) && ($type === 'revenue_on_credit' ? $net < 0 : $net > 0)
                    && ($type !== 'equipment_acquisition' || str_starts_with($row['compte'], '2351') || str_starts_with($row['compte'], '2355'))) { $targets[] = $i; }
                $creditCounter = in_array($type, ['expense_accrual', 'supplier_payment', 'equipment_acquisition'], true);
                if (str_starts_with($row['compte'], $counterPrefix) && ($creditCounter ? ($type === 'supplier_payment' ? $net < 0 : $net <= 0) : $net >= 0)) { $counterparts[] = $i; }
            }
            if ($targets !== [] && $counterparts !== []) { $operations[] = [$type, $targets, $counterparts]; }
        }
        if ($operations === [] && $nearCount + $anomalyCount > 0) {
            throw new RuntimeException('No supported target/counterpart pair; no scenario generated.');
        }
        $parent = hash('sha256', json_encode($source, JSON_THROW_ON_ERROR));
        $rng = new Randomizer(new Mt19937($seed));
        $result = ['parent_transition' => "$previousYear->$currentYear", 'parent_scenario' => $parent,
            'original' => ['scenario_id' => $parent, 'scenario_type' => 'original', 'synthetic_label' => null,
                'parent_scenario' => null, 'parent_transition' => "$previousYear->$currentYear", 'random_seed' => $seed,
                'operation_type' => 'none', 'magnitude' => 0.0, 'targeted_accounts' => [],
                'counterpart_accounts' => [], 'modified_accounts' => [], 'description' => 'Unmodified historical snapshot; not labelled normal.',
                'snapshot' => $source, 'observations' => $originalFeatures], 'scenarios' => []];
        foreach ([['synthetic_near_normal', $nearCount, 1, 5, 0], ['synthetic_anomaly', $anomalyCount, 50, 100, 1]] as [$family, $count, $min, $max, $label]) {
            for ($j = 0; $j < $count; $j++) {
                [$type, $targets, $counterparts] = $operations[$rng->getInt(0, count($operations) - 1)];
                $ti = $targets[$rng->getInt(0, count($targets) - 1)];
                $ci = $counterparts[$rng->getInt(0, count($counterparts) - 1)];
                $bounded = in_array($type, ['supplier_payment', 'customer_collection'], true);
                $upper = $bounded && $label === 1 ? 80 : $max;
                $percent = count($allowedOperations) === 1 ? $rng->getInt($min * 100, $upper * 100) / 100 : $rng->getInt($min, $upper);
                $base = abs($this->cents($source[$ti]['solde_debiteur']) - $this->cents($source[$ti]['solde_crediteur']));
                if ($type === 'supplier_payment') {
                    $base = min($base, abs($this->cents($source[$ci]['solde_debiteur']) - $this->cents($source[$ci]['solde_crediteur'])));
                }
                $amount = (int) round($base * $percent / 100);
                if ($amount <= 0) { throw new RuntimeException('Perturbation rounds to zero.'); }
                $copy = $source;
                $positiveTarget = in_array($type, ['expense_accrual', 'equipment_acquisition'], true);
                $this->apply($copy[$ti], $positiveTarget ? $amount : -$amount);
                $this->apply($copy[$ci], $positiveTarget ? -$amount : $amount);
                $this->validateAccounting($copy, $currentYear);
                $target = $source[$ti]['compte']; $counter = $source[$ci]['compte'];
                $id = hash('sha256', "$parent:$seed:$family:$j:$type:$target:$counter:$percent");
                $observations = $this->features->buildFromSnapshot($userId, $societeId, $previousYear, $currentYear, $copy);
                foreach ($observations as &$observation) {
                    $isTarget = $observation['account'] === $target;
                    $isCounter = $observation['account'] === $counter;
                    $observation += ['scenario_id' => $id, 'scenario_type' => $family,
                        'synthetic_label' => $isTarget ? $label : 0, 'targeted_account' => $isTarget,
                        'targeted_anomaly' => $isTarget && $label === 1, 'affected_by_counterpart' => $isCounter,
                        'counterpart_effect' => $isCounter, 'original_account' => $observation['account']];
                }
                unset($observation);
                $result['scenarios'][] = ['scenario_id' => $id, 'parent_scenario' => $parent,
                    'parent_transition' => "$previousYear->$currentYear", 'random_seed' => $seed,
                    'scenario_type' => $family, 'operation_type' => $type, 'magnitude' => $percent / 100,
                    'amount' => $amount / 100, 'targeted_accounts' => [$target], 'counterpart_accounts' => [$counter],
                    'magnitude_basis' => $type === 'supplier_payment' ? 'min(bank_available,supplier_debt)' : 'absolute_target_balance',
                    'modified_accounts' => [$target, $counter], 'synthetic_label' => $label,
                    'description' => "$type: $target / $counter, +$percent% of target magnitude; pre-closing simulation, not a fraud label.",
                    'snapshot' => $copy, 'observations' => $observations];
            }
        }

        return $result;
    }

    private function apply(array &$row, int $delta): void
    {
        $net = $this->cents($row['solde_debiteur']) - $this->cents($row['solde_crediteur']) + $delta;
        $row['solde_debiteur'] = max($net, 0) / 100;
        $row['solde_crediteur'] = max(-$net, 0) / 100;
    }

    private function cents(mixed $value): int
    {
        if (!is_numeric($value) || !is_finite((float) $value) || (float) $value < 0 || (float) $value > 9999999999999.99
            || abs((float) $value * 100 - round((float) $value * 100)) > 0.01) {
            throw new RuntimeException('Expected nonnegative finite two-decimal balance amount.');
        }

        return (int) round((float) $value * 100);
    }

    private function validateAccounting(array $snapshot, int $year): void
    {
        $sum = 0;
        foreach ($snapshot as $row) {
            if ((int) $row['exercice'] !== $year) { continue; }
            $d = $this->cents($row['solde_debiteur']); $c = $this->cents($row['solde_crediteur']);
            if ($d > 0 && $c > 0) { throw new RuntimeException('Snapshot requires net debit/credit balances.'); }
            if (str_starts_with($row['compte'], '119') && $d !== $c) {
                throw new RuntimeException('Unsupported closing stage: nonzero account 119.');
            }
            $sum += $d - $c;
        }
        // With 119 zero, net classes 1..5 = products - charges follows from this equality.
        if ($sum !== 0) { throw new RuntimeException('Unbalanced experimental snapshot.'); }
        foreach ($snapshot as $row) {
            if (!preg_match('/^[1-7][0-9]*$/D', $row['compte'])) {
                throw new RuntimeException('Unsupported account class for balance/CPC invariant.');
            }
        }
    }
}
