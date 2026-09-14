<?php

// Technical CLI only. Read-only PDO transaction; private JSON files, no business writes.
require dirname(__DIR__).'/vendor/autoload.php';

use App\Services\AccountingAnomalyFeatureService;
use App\Services\AccountingAnomalyScenarioService;

if ($argc !== 5 || !in_array($argv[1], ['experiment', 'holdout'], true)) {
    throw new RuntimeException('Usage: php ml/export.php experiment|holdout user_id societe_id run_name');
}
[$mode, $user, $societe, $run] = array_slice($argv, 1);
if (!ctype_digit($user) || !ctype_digit($societe) || !preg_match('/^[a-zA-Z0-9_-]+$/D', $run)) {
    throw new RuntimeException('Invalid CLI context.');
}
$root = dirname(__DIR__);
$directory = $root.'/storage/app/private/ml/accounting_anomaly/'.$run;
$filename = $directory.'/'.($mode === 'experiment' ? 'dataset.json' : 'holdout.json');
if (file_exists($filename)) { throw new RuntimeException('Refusing to overwrite dataset.'); }
if ($mode === 'holdout' && !file_exists($directory.'/model.joblib')) { throw new RuntimeException('Train first; holdout export is final only.'); }
$env = Dotenv\Dotenv::createArrayBacked($root)->load();
$pdo = new PDO('mysql:host='.$env['DB_HOST'].';port='.$env['DB_PORT'].';dbname='.$env['DB_DATABASE'], $env['DB_USERNAME'], $env['DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec('SET TRANSACTION READ ONLY'); $pdo->beginTransaction();
try {
    $owner = $pdo->prepare('SELECT id FROM societes WHERE id=? AND user_id=?'); $owner->execute([$societe, $user]);
    if (!$owner->fetchColumn()) { throw new RuntimeException('Foreign society.'); }
    $years = $mode === 'experiment' ? [2024, 2025] : [2025, 2026];
    $query = $pdo->prepare('SELECT * FROM balance_items WHERE user_id=? AND societe_id=? AND exercice IN (?,?) ORDER BY exercice,compte');
    $query->execute([$user, $societe, ...$years]); $source = $query->fetchAll(PDO::FETCH_ASSOC);
    $before = hash('sha256', serialize($source));
    $features = new AccountingAnomalyFeatureService;
    if ($mode === 'holdout') {
        $data = $features->buildFromSnapshot((int) $user, (int) $societe, ...[...$years, $source]);
    } else {
        $generator = new AccountingAnomalyScenarioService($features);
        $data = ['protocol' => 'account-transition-v1', 'train' => [], 'validation' => [], 'test' => []];
        $seen = [];
        $enriched = str_starts_with($run, 'phase3b_');
        $families = $enriched ? ['expense_accrual', 'revenue_on_credit', 'supplier_payment', 'customer_collection', 'equipment_acquisition'] : [null];
        foreach (['train' => 42, 'validation' => 142, 'test' => 242] as $split => $seed) {
          foreach ($families as $familyIndex => $family) {
            $desired = $enriched ? 6 : 10;
            $near = $anomaly = 0;
            for ($attempt = 0; $near < $desired || ($split !== 'train' && $anomaly < $desired); $attempt++) {
                if ($attempt > 100) { throw new RuntimeException('Unable to obtain distinct scenarios.'); }
                $batch = $generator->generate($source, (int) $user, (int) $societe, 2024, 2025, $seed + $familyIndex * 1000 + $attempt, $desired, $split === 'train' ? 0 : $desired,
                    $family === null ? ['expense_accrual', 'revenue_on_credit'] : [$family]);
                if ($split === 'train' && $attempt === 0 && $familyIndex === 0) {
                    $original = $batch['original'];
                    foreach ($original['observations'] as &$r) { $r += ['scenario_id' => $original['scenario_id'], 'scenario_type' => 'original', 'synthetic_label' => null, 'targeted_anomaly' => false, 'counterpart_effect' => false]; } unset($r);
                    unset($original['snapshot']); $data['train'][] = $original;
                    $seen[hash('sha256', serialize(array_column($original['observations'], 'features')))] = true;
                }
                foreach ($batch['scenarios'] as $scenario) {
                    $isNear = $scenario['scenario_type'] === 'synthetic_near_normal';
                    if (($isNear && $near >= $desired) || (!$isNear && $anomaly >= $desired)) { continue; }
                    $fp = hash('sha256', serialize(array_column($scenario['observations'], 'features')));
                    if (isset($seen[$fp])) { continue; } $seen[$fp] = true;
                    unset($scenario['snapshot']); $data[$split][] = $scenario;
                    if ($isNear) { $near++; } else { $anomaly++; }
                }
            }
          }
        }
    }
    $query->execute([$user, $societe, ...$years]);
    if ($before !== hash('sha256', serialize($query->fetchAll(PDO::FETCH_ASSOC)))) { throw new RuntimeException('Source fingerprint changed.'); }
    $pdo->rollBack();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); } throw $exception;
}
if (!is_dir($directory)) { mkdir($directory, 0700, true); }
file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
echo $filename.PHP_EOL;
