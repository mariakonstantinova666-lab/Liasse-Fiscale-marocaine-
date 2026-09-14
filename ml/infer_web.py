"""Inference only. Server environment selects a fixed, hash-approved local artifact."""
import hashlib
import io
import json
import math
import os
from pathlib import Path
import re
import sys

sys.path.insert(0, str(Path(__file__).resolve().parent))
from common import FEATURES, PROTOCOL, matrix

MAX_BYTES = 4 * 1024 * 1024
MAX_ROWS = 5000


def decode(text):
    def unique(pairs):
        result = {}
        for key, value in pairs:
            if key in result:
                raise ValueError('Duplicate JSON key')
            result[key] = value
        return result
    return json.loads(text, object_pairs_hook=unique,
                      parse_constant=lambda v: (_ for _ in ()).throw(ValueError('Nonfinite JSON')))


def validate(payload):
    if not isinstance(payload, dict) or set(payload) != {'protocol', 'previous_exercise', 'exercise', 'observations'}:
        raise ValueError('Invalid envelope')
    p, n = payload['previous_exercise'], payload['exercise']
    if payload['protocol'] != PROTOCOL or type(p) is not int or type(n) is not int or not 1000 <= p < n <= 9999 or n != p + 1:
        raise ValueError('Invalid context')
    rows = payload['observations']
    if not isinstance(rows, list) or not 1 <= len(rows) <= MAX_ROWS:
        raise ValueError('Invalid observation count')
    accounts = set()
    for row in rows:
        if not isinstance(row, dict) or set(row) != {'account', 'label', 'features'}:
            raise ValueError('Invalid observation')
        account = row['account']
        if not isinstance(account, str) or not re.fullmatch(r'[1-9][0-9]{0,63}', account) or account in accounts or not isinstance(row['label'], str):
            raise ValueError('Invalid account metadata')
        accounts.add(account)
    matrix(rows)
    return rows


def approved_paths(env):
    root = Path(__file__).resolve().parent.parent / 'storage/app/private/ml/accounting_anomaly/phase3b_v1'
    model = Path(env['ACCOUNTING_ANOMALY_MODEL_PATH'])
    metadata = Path(env['ACCOUNTING_ANOMALY_METADATA_PATH'])
    # No symlink escape, including a symlinked experiment directory.
    if model.resolve(strict=True) != root / 'model.joblib' or metadata.resolve(strict=True) != root / 'metadata.json':
        raise ValueError('Unauthorized artifact location')
    expected = env['ACCOUNTING_ANOMALY_MODEL_SHA256'].lower()
    if not re.fullmatch(r'[0-9a-f]{64}', expected) or hashlib.sha256(model.read_bytes()).hexdigest() != expected:
        raise ValueError('Artifact fingerprint mismatch')
    if env['ACCOUNTING_ANOMALY_MODEL_VERSION'] != 'phase3b_v1':
        raise ValueError('Unexpected model version')
    return model, metadata


def infer(payload, env):
    rows = validate(payload)
    model_path, metadata_path = approved_paths(env)
    metadata = decode(metadata_path.read_text(encoding='utf-8'))
    import joblib
    import sklearn
    from sklearn.ensemble import IsolationForest
    if metadata.get('protocol') != PROTOCOL or metadata.get('feature_order') != FEATURES or metadata.get('random_state') != 42 or metadata.get('sklearn_version') != sklearn.__version__:
        raise ValueError('Incompatible metadata')
    # Fingerprint and resolved location were checked BEFORE deserialization.
    model_bytes = model_path.read_bytes()
    if hashlib.sha256(model_bytes).hexdigest() != env['ACCOUNTING_ANOMALY_MODEL_SHA256'].lower():
        raise ValueError('Artifact changed before loading')
    artifact = joblib.load(io.BytesIO(model_bytes))
    threshold = artifact['threshold']
    if type(threshold) not in (int, float) or not math.isfinite(threshold) or threshold != metadata.get('threshold'):
        raise ValueError('Invalid threshold')
    if any(artifact.get(k) != metadata.get(k) for k in ('protocol', 'feature_order', 'random_state', 'sklearn_version', 'dataset_hash')):
        raise ValueError('Artifact metadata mismatch')
    model = artifact['model']
    if not isinstance(model, IsolationForest) or model.n_estimators != 200 or model.random_state != 42 or model.max_samples != 'auto' or model.contamination != 'auto':
        raise ValueError('Unexpected estimator')
    scores = model.score_samples(matrix(rows))
    if len(scores) != len(rows) or not all(math.isfinite(float(s)) for s in scores):
        raise ValueError('Invalid model output')
    return dict(protocol=PROTOCOL, model_version='phase3b_v1', threshold=threshold,
                observations_count=len(rows), results=[dict(account=r['account'], score_samples=float(s), flagged=bool(s <= threshold)) for r, s in zip(rows, scores)])


def main():
    try:
        if len(sys.argv) != 1:
            raise ValueError('Arguments not accepted')
        raw = sys.stdin.buffer.read(MAX_BYTES + 1)
        if len(raw) > MAX_BYTES:
            raise ValueError('Input size exceeded')
        result = infer(decode(raw.decode('utf-8')), os.environ)
        sys.stdout.write(json.dumps(result, allow_nan=False))
        return 0
    except Exception:
        # Never disclose payload, paths or exception strings.
        sys.stderr.write('Accounting anomaly inference failed.\n')
        return 1


if __name__ == '__main__':
    sys.exit(main())
