<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Process;

/** Isolated inference: no database, fiscal services or session access. */
class AccountingAnomalyInferenceService
{
    private const FEATURES = ['symmetric_change', 'class_scaled_change', 'share_class_current', 'share_class_change', 'share_total_change'];
    private const PROTOCOL = 'account-transition-v1';

    public function infer(array $observations, int $previousExercise, int $exercise): array
    {
        $config = config('accounting_anomaly');
        if (($config['enabled'] ?? false) !== true) {
            throw new RuntimeException('IA_DISABLED');
        }
        $python = realpath($config['python_path'] ?? '');
        if (!$python || !is_file($python) || (PHP_OS_FAMILY !== 'Windows' && !is_executable($python))) {
            throw new RuntimeException('IA_PYTHON_UNAVAILABLE');
        }
        $root = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, storage_path('app/private/ml/accounting_anomaly/phase3b_v1'));
        $model = realpath($config['model_path'] ?? '');
        $metadataPath = realpath($config['model_metadata_path'] ?? '');
        if (!$model || !is_file($model)) { throw new RuntimeException('IA_MODEL_UNAVAILABLE'); }
        if (!$metadataPath || !is_file($metadataPath)) { throw new RuntimeException('IA_METADATA_UNAVAILABLE'); }
        if ($model !== $root.DIRECTORY_SEPARATOR.'model.joblib' || $metadataPath !== $root.DIRECTORY_SEPARATOR.'metadata.json') {
            throw new RuntimeException('IA_ARTIFACT_LOCATION');
        }
        $hash = strtolower($config['model_sha256'] ?? '');
        if (!preg_match('/^[a-f0-9]{64}$/D', $hash) || !hash_equals($hash, hash_file('sha256', $model))) {
            throw new RuntimeException('IA_FINGERPRINT');
        }
        $metadata = json_decode(file_get_contents($metadataPath), true);
        if (($config['model_version'] ?? null) !== 'phase3b_v1' || !is_array($metadata)
            || ($metadata['protocol'] ?? null) !== self::PROTOCOL || ($metadata['feature_order'] ?? null) !== self::FEATURES
            || ($metadata['random_state'] ?? null) !== 42 || !$this->finite($metadata['threshold'] ?? null)) {
            throw new RuntimeException('IA_METADATA_INVALID');
        }
        if ($previousExercise < 1000 || $exercise > 9999 || $exercise !== $previousExercise + 1 || !array_is_list($observations) || count($observations) < 1 || count($observations) > 5000) {
            throw new RuntimeException('IA_INPUT_INVALID');
        }
        $rows = []; $accounts = [];
        foreach ($observations as $row) {
            $account = $row['account'] ?? null;
            if (!is_string($account) || !preg_match('/^[1-9][0-9]{0,63}$/D', $account) || isset($accounts['a'.$account])
                || !is_string($row['label'] ?? null) || !is_array($row['features'] ?? null) || array_keys($row['features']) !== self::FEATURES) {
                throw new RuntimeException('IA_INPUT_INVALID');
            }
            foreach ($row['features'] as $value) {
                if (!$this->finite($value)) { throw new RuntimeException('IA_INPUT_INVALID'); }
            }
            $accounts['a'.$account] = true;
            $rows[] = array_intersect_key($row, array_flip(['account', 'label', 'features']));
        }
        try {
            $input = json_encode(['protocol' => self::PROTOCOL, 'previous_exercise' => $previousExercise, 'exercise' => $exercise, 'observations' => $rows], JSON_THROW_ON_ERROR);
        } catch (\Throwable) { throw new RuntimeException('IA_INPUT_INVALID'); }
        if (strlen($input) > 4194304) { throw new RuntimeException('IA_INPUT_TOO_LARGE'); }
        $timeout = $config['timeout'] ?? 15;
        if (!$this->finite($timeout) || $timeout <= 0 || $timeout > 60) { throw new RuntimeException('IA_TIMEOUT_CONFIG'); }
        $env = ['ACCOUNTING_ANOMALY_MODEL_PATH' => $model, 'ACCOUNTING_ANOMALY_METADATA_PATH' => $metadataPath,
            'ACCOUNTING_ANOMALY_MODEL_SHA256' => $hash, 'ACCOUNTING_ANOMALY_MODEL_VERSION' => 'phase3b_v1'];
        $output = $this->execute($python, $input, $env, (float) $timeout);
        if ($output === '') { throw new RuntimeException('IA_OUTPUT_EMPTY'); }
        if (strlen($output) > 4194304) { throw new RuntimeException('IA_OUTPUT_TOO_LARGE'); }
        try { $response = json_decode($output, true, 512, JSON_THROW_ON_ERROR); }
        catch (\Throwable) { throw new RuntimeException('IA_OUTPUT_JSON'); }
        if (!is_array($response) || count($response) !== 5 || ($response['protocol'] ?? null) !== self::PROTOCOL
            || ($response['model_version'] ?? null) !== 'phase3b_v1' || !$this->finite($response['threshold'] ?? null)
            || (float) $response['threshold'] !== (float) $metadata['threshold'] || ($response['observations_count'] ?? null) !== count($rows)
            || !is_array($response['results'] ?? null) || !array_is_list($response['results']) || count($response['results']) !== count($rows)) {
            throw new RuntimeException('IA_OUTPUT_INVALID');
        }
        $seen = [];
        foreach ($response['results'] as $result) {
            $account = $result['account'] ?? null;
            if (!is_array($result) || count($result) !== 3 || !is_string($account) || !isset($accounts['a'.$account]) || isset($seen['a'.$account])
                || !$this->finite($result['score_samples'] ?? null) || !is_bool($result['flagged'] ?? null)
                || $result['flagged'] !== ($result['score_samples'] <= $response['threshold'])) {
                throw new RuntimeException('IA_OUTPUT_INVALID');
            }
            $seen['a'.$account] = true;
        }
        return $response;
    }

    protected function execute(string $python, string $input, array $env, float $timeout): string
    {
        // Only the allowlisted values reach the child; no application secrets.
        foreach (['SystemRoot', 'WINDIR', 'PATH', 'TEMP', 'TMP'] as $key) {
            if (getenv($key) !== false) { $env[$key] = getenv($key); }
        }
        foreach (array_keys(getenv()) as $key) {
            if (!array_key_exists($key, $env)) { $env[$key] = false; }
        }
        $process = new Process([$python, '-I', '-B', base_path('ml/infer_web.py')], base_path(), $env, $input, $timeout);
        try {
            $process->run(function ($type, $buffer) use ($process) {
                if (strlen($process->getOutput()) + strlen($process->getErrorOutput()) > 4194304) {
                    $process->stop(0);
                    throw new RuntimeException('IA_OUTPUT_TOO_LARGE');
                }
            });
        } catch (\Symfony\Component\Process\Exception\ProcessTimedOutException) {
            throw new RuntimeException('IA_TIMEOUT');
        } catch (\Throwable) { throw new RuntimeException('IA_PROCESS_FAILED'); }
        if (!$process->isSuccessful()) { throw new RuntimeException('IA_PROCESS_FAILED'); }
        return $process->getOutput();
    }

    private function finite(mixed $value): bool
    {
        return (is_int($value) || is_float($value)) && is_finite((float) $value);
    }
}
