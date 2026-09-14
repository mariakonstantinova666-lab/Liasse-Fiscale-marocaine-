<?php

namespace App\Services;

use App\Models\BalanceItem;
use App\Models\Societe;
use InvalidArgumentException;
use RuntimeException;

/** Read-only experimental features. No fiscal services, session or persistence. */
class AccountingAnomalyFeatureService
{
    public function buildTransition(int $userId, int $societeId, int $previousYear, int $currentYear): array
    {
        if ($userId <= 0 || $societeId <= 0 || $previousYear < 1000 || $currentYear > 9999 || $currentYear !== $previousYear + 1) {
            throw new InvalidArgumentException('Expected a valid context and consecutive four-digit years.');
        }
        if (!Societe::query()->whereKey($societeId)->where('user_id', $userId)->exists()) {
            throw new RuntimeException('Society does not belong to this user.');
        }

        $rows = BalanceItem::query()->where('user_id', $userId)->where('societe_id', $societeId)
            ->whereIn('exercice', [$previousYear, $currentYear])->orderBy('compte')->get();

        return $this->buildFromSnapshot($userId, $societeId, $previousYear, $currentYear, $rows->toArray());
    }

    /** Pure calculation entry point: snapshots are never persisted. */
    public function buildFromSnapshot(int $userId, int $societeId, int $previousYear, int $currentYear, array $rows): array
    {
        if ($userId <= 0 || $societeId <= 0 || $previousYear < 1000 || $currentYear > 9999 || $currentYear !== $previousYear + 1) {
            throw new InvalidArgumentException('Expected a valid context and consecutive four-digit years.');
        }
        foreach ($rows as $row) {
            if (!is_array($row) || !isset($row['compte'])) {
                throw new RuntimeException('Malformed snapshot row.');
            }
        }
        usort($rows, fn ($a, $b) => strcmp((string) $a['compte'], (string) $b['compte']));
        $balances = [$previousYear => [], $currentYear => []];
        $classes = [$previousYear => [], $currentYear => []];
        $totals = [$previousYear => 0.0, $currentYear => 0.0];
        foreach ($rows as $row) {
            if (!is_array($row) || !isset($row['user_id'], $row['societe_id'], $row['exercice'], $row['compte'], $row['libelle'], $row['solde_debiteur'], $row['solde_crediteur'])
                || (int) $row['user_id'] !== $userId || (int) $row['societe_id'] !== $societeId
                || !in_array((int) $row['exercice'], [$previousYear, $currentYear], true)) {
                throw new RuntimeException('Incoherent snapshot context.');
            }
            $row = (object) $row;
            $year = (int) $row->exercice;
            $account = (string) $row->compte;
            // Prefix keys to preserve exact account strings, including leading zeroes.
            $key = 'account:'.$account;
            if (preg_match('/^[1-9][0-9]*$/D', $account) !== 1) {
                throw new RuntimeException('Invalid numeric account: '.$account);
            }
            if (isset($balances[$year][$key])) {
                throw new RuntimeException('Duplicate account in year '.$year.': '.$account);
            }
            foreach (['solde_debiteur', 'solde_crediteur'] as $column) {
                if (!is_numeric($row->$column) || !is_finite((float) $row->$column)) {
                    throw new RuntimeException('Invalid balance amount.');
                }
            }
            $net = (float) $row->solde_debiteur - (float) $row->solde_crediteur;
            $class = (int) $account[0];
            $balances[$year][$key] = ['account' => $account, 'label' => (string) $row->libelle, 'class' => $class, 'net' => $net];
            $classes[$year][$class] = ($classes[$year][$class] ?? 0.0) + abs($net);
            $totals[$year] += abs($net);
        }
        if ($balances[$previousYear] === [] || $balances[$currentYear] === []) {
            throw new RuntimeException('Both annual balances are required.');
        }
        if (array_diff_key($balances[$previousYear], $balances[$currentYear]) !== []
            || array_diff_key($balances[$currentYear], $balances[$previousYear]) !== []) {
            throw new RuntimeException('Account sets differ between years; missing balances are not imputed.');
        }

        $observations = [];
        foreach ($balances[$previousYear] as $key => $previous) {
            $current = $balances[$currentYear][$key];
            $p = $previous['net'];
            $n = $current['net'];
            $cp = $classes[$previousYear][$previous['class']];
            $cn = $classes[$currentYear][$current['class']];
            $delta = $n - $p;
            $features = [
                'symmetric_change' => $this->ratio(2 * $delta, abs($n) + abs($p)),
                'class_scaled_change' => $this->ratio($delta, $cp),
                'share_class_current' => $this->ratio(abs($n), $cn),
                'share_class_change' => $this->ratio(abs($n), $cn) - $this->ratio(abs($p), $cp),
                'share_total_change' => $this->ratio(abs($n), $totals[$currentYear]) - $this->ratio(abs($p), $totals[$previousYear]),
            ];
            foreach (array_merge($features, [$delta, $p, $n]) as $value) {
                if (!is_finite($value)) {
                    throw new RuntimeException('Non-finite feature calculation.');
                }
            }
            $relative = $p == 0.0 ? null : $delta / abs($p);
            if ($relative !== null && !is_finite($relative)) {
                throw new RuntimeException('Non-finite relative change.');
            }
            $observations[] = [
                'user_id' => $userId, 'societe_id' => $societeId,
                'account' => $previous['account'], 'label' => $current['label'],
                'previous_label' => $previous['label'], 'label_changed' => $previous['label'] !== $current['label'],
                'account_class' => $previous['class'], 'previous_year' => $previousYear, 'current_year' => $currentYear,
                'previous_balance' => $p, 'current_balance' => $n,
                'absolute_change' => $delta, 'relative_change' => $relative,
                'previous_zero' => $p == 0.0, 'current_zero' => $n == 0.0,
                'sign_change' => ($p > 0 && $n < 0) || ($p < 0 && $n > 0),
                'zero_denominators' => ['class_previous' => $cp == 0.0, 'class_current' => $cn == 0.0,
                    'total_previous' => $totals[$previousYear] == 0.0, 'total_current' => $totals[$currentYear] == 0.0],
                'features' => $features,
            ];
        }

        return $observations;
    }

    private function ratio(float $numerator, float $denominator): float
    {
        if (!is_finite($numerator) || !is_finite($denominator)) {
            throw new RuntimeException('Non-finite numerator or denominator.');
        }

        return $denominator == 0.0 ? 0.0 : $numerator / $denominator;
    }
}
