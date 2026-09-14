<?php

namespace Tests\Unit;

use App\Services\AccountingAnomalyInferenceService;
use RuntimeException;
use Tests\TestCase;

class AccountingAnomalyInferenceServiceTest extends TestCase
{
    public function test_disabled_module_is_explicit(): void
    {
        config(['accounting_anomaly.enabled' => false]);
        $this->expectExceptionMessage('IA_DISABLED');
        (new AccountingAnomalyInferenceService)->infer([], 2025, 2026);
    }

    public function test_python_absence_is_explicit(): void
    {
        config(['accounting_anomaly.enabled' => true, 'accounting_anomaly.python_path' => '/missing/python']);
        $this->expectExceptionMessage('IA_PYTHON_UNAVAILABLE');
        (new AccountingAnomalyInferenceService)->infer([], 2025, 2026);
    }

    public function test_response_contract_and_session_preservation(): void
    {
        // Local artifact preflight is real; subprocess is simulated, never Python.
        $model = storage_path('app/private/ml/accounting_anomaly/phase3b_v1/model.joblib');
        if (!is_file($model)) { $this->markTestSkipped('Trusted local model is required for preflight fixtures.'); }
        config(['accounting_anomaly.enabled' => true, 'accounting_anomaly.python_path' => PHP_BINARY,
            'accounting_anomaly.model_sha256' => hash_file('sha256', $model)]);
        $features = array_fill_keys(['symmetric_change', 'class_scaled_change', 'share_class_current', 'share_class_change', 'share_total_change'], 0.0);
        $rows = [['account' => '6131', 'label' => 'Test', 'features' => $features], ['account' => '4411', 'label' => 'Test', 'features' => $features]];
        $threshold = json_decode(file_get_contents(config('accounting_anomaly.model_metadata_path')), true)['threshold'];
        $valid = ['protocol' => 'account-transition-v1', 'model_version' => 'phase3b_v1', 'threshold' => $threshold,
            'observations_count' => 2, 'results' => [['account' => '6131', 'score_samples' => -0.5, 'flagged' => false], ['account' => '4411', 'score_samples' => -0.8, 'flagged' => true]]];
        $service = new class extends AccountingAnomalyInferenceService {
            public string $output;
            protected function execute(string $python, string $input, array $env, float $timeout): string {
                if ($this->output === 'IA_TIMEOUT') { throw new RuntimeException('IA_TIMEOUT'); }
                return $this->output;
            }
        };
        session(['annee_exercice' => 2026]); $before = session()->all();
        $service->output = json_encode($valid);
        $this->assertSame($valid, $service->infer($rows, 2025, 2026));
        $this->assertSame($before, session()->all());
        $service->output = 'IA_TIMEOUT';
        try { $service->infer($rows, 2025, 2026); $this->fail('Timeout accepted'); }
        catch (RuntimeException $e) { $this->assertSame('IA_TIMEOUT', $e->getMessage()); }
        foreach (['missing', 'extra', 'duplicate', 'flag', 'score', 'threshold', 'version', 'json', 'empty'] as $case) {
            $bad = $valid;
            if ($case === 'missing') { array_pop($bad['results']); }
            if ($case === 'extra') { $bad['results'][0]['account'] = '999'; }
            if ($case === 'duplicate') { $bad['results'][1]['account'] = '6131'; }
            if ($case === 'flag') { $bad['results'][0]['flagged'] = true; }
            if ($case === 'score') { $bad['results'][0]['score_samples'] = 'NaN'; }
            if ($case === 'threshold') { $bad['threshold'] = null; }
            if ($case === 'version') { $bad['model_version'] = 'bad'; }
            $service->output = $case === 'json' ? '{' : ($case === 'empty' ? '' : json_encode($bad));
            try { $service->infer($rows, 2025, 2026); $this->fail('Invalid output accepted: '.$case); }
            catch (RuntimeException $e) { $this->assertStringStartsWith('IA_OUTPUT_', $e->getMessage()); }
        }
        config(['accounting_anomaly.model_sha256' => str_repeat('0', 64)]);
        $this->expectExceptionMessage('IA_FINGERPRINT');
        $service->infer($rows, 2025, 2026);
    }

    public function test_missing_artifacts_and_process_errors_are_controlled(): void
    {
        config(['accounting_anomaly.enabled' => true, 'accounting_anomaly.python_path' => PHP_BINARY,
            'accounting_anomaly.model_path' => '/absent/model.joblib']);
        try { (new AccountingAnomalyInferenceService)->infer([], 2025, 2026); $this->fail(); }
        catch (RuntimeException $e) { $this->assertSame('IA_MODEL_UNAVAILABLE', $e->getMessage()); }
        config(['accounting_anomaly.model_path' => storage_path('app/private/ml/accounting_anomaly/phase3b_v1/model.joblib')]);
        if (!is_file(config('accounting_anomaly.model_path'))) { return; }
        config(['accounting_anomaly.model_metadata_path' => '/absent/metadata.json']);
        try { (new AccountingAnomalyInferenceService)->infer([], 2025, 2026); $this->fail(); }
        catch (RuntimeException $e) { $this->assertSame('IA_METADATA_UNAVAILABLE', $e->getMessage()); }
        $service = new class extends AccountingAnomalyInferenceService {
            public function runFailure(): string { return $this->execute(PHP_BINARY, '{}', [], 1.0); }
        };
        try { $service->runFailure(); $this->fail('Wrong executable unexpectedly succeeded'); }
        catch (RuntimeException $e) { $this->assertSame('IA_PROCESS_FAILED', $e->getMessage()); }
    }
}
