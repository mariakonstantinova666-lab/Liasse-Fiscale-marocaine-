<?php

return [
    'enabled' => env('ACCOUNTING_ANOMALY_ENABLED', false),
    'python_path' => env('ACCOUNTING_ANOMALY_PYTHON_PATH', base_path('ml/.venv/'.(PHP_OS_FAMILY === 'Windows' ? 'Scripts/python.exe' : 'bin/python'))),
    'model_path' => env('ACCOUNTING_ANOMALY_MODEL_PATH', storage_path('app/private/ml/accounting_anomaly/phase3b_v1/model.joblib')),
    'model_metadata_path' => env('ACCOUNTING_ANOMALY_METADATA_PATH', storage_path('app/private/ml/accounting_anomaly/phase3b_v1/metadata.json')),
    'model_version' => env('ACCOUNTING_ANOMALY_MODEL_VERSION', 'phase3b_v1'),
    'model_sha256' => env('ACCOUNTING_ANOMALY_MODEL_SHA256', ''),
    'timeout' => (float) env('ACCOUNTING_ANOMALY_TIMEOUT', 15),
];
