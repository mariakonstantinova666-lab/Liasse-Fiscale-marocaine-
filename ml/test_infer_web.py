import copy
import json
import unittest
import hashlib
from pathlib import Path
from unittest.mock import patch
import infer_web as web
from common import FEATURES


class InferenceTests(unittest.TestCase):
    def payload(self):
        return dict(protocol='account-transition-v1', previous_exercise=2025, exercise=2026,
                    observations=[dict(account='6131', label='Metadata', features=dict.fromkeys(FEATURES, 0.0))])

    def test_valid_payload_keeps_features(self):
        p = self.payload(); original = copy.deepcopy(p)
        self.assertEqual(web.validate(p), p['observations'])
        self.assertEqual(p, original)

    def test_invalid_payloads(self):
        for kind in ('protocol', 'years', 'bool_year', 'empty', 'duplicate', 'missing', 'extra', 'nan', 'inf', 'path'):
            p = self.payload()
            if kind == 'protocol': p['protocol'] = 'bad'
            if kind == 'years': p['exercise'] = 2028
            if kind == 'bool_year': p['exercise'] = True
            if kind == 'empty': p['observations'] = []
            if kind == 'duplicate': p['observations'] *= 2
            if kind == 'missing': del p['observations'][0]['features'][FEATURES[0]]
            if kind == 'extra': p['observations'][0]['features']['extra'] = 1
            if kind in ('nan', 'inf'): p['observations'][0]['features'][FEATURES[0]] = float(kind)
            if kind == 'path': p['model_path'] = 'evil.joblib'
            with self.subTest(kind=kind), self.assertRaises(ValueError): web.validate(p)

    def test_duplicate_json_and_nonfinite_rejected(self):
        for raw in ('{"x":1,"x":2}', '{"x":NaN}', '{'):
            with self.assertRaises(ValueError): web.decode(raw)

    def test_unapproved_model_never_deserialized(self):
        with patch('joblib.load') as load:
            with self.assertRaises((KeyError, ValueError, FileNotFoundError)):
                web.infer(self.payload(), {})
            load.assert_not_called()

    def test_real_phase3b_matches_holdout(self):
        root = Path(web.__file__).resolve().parent.parent / 'storage/app/private/ml/accounting_anomaly/phase3b_v1'
        if not (root / 'model.joblib').exists():
            self.skipTest('Local trusted artifact not deployed')
        env = dict(ACCOUNTING_ANOMALY_MODEL_PATH=str(root / 'model.joblib'),
                   ACCOUNTING_ANOMALY_METADATA_PATH=str(root / 'metadata.json'),
                   ACCOUNTING_ANOMALY_MODEL_SHA256='fecfd3b6faa4f2d25f1c1782e14c5c3bb78e8cd567b4db6affe4828776814bc5',
                   ACCOUNTING_ANOMALY_MODEL_VERSION='phase3b_v1')
        rows = web.decode((root / 'holdout.json').read_text(encoding='utf-8'))
        p = dict(protocol='account-transition-v1', previous_exercise=2025, exercise=2026,
                 observations=[{k: r[k] for k in ('account', 'label', 'features')} for r in rows])
        result = web.infer(p, env)
        old = web.decode((root / 'holdout_scores.json').read_text(encoding='utf-8'))
        self.assertEqual(len(result['results']), 61)
        self.assertEqual(sum(r['flagged'] for r in result['results']), 0)
        self.assertEqual({r['account']: r['score_samples'] for r in result['results']}, {r['account']: r['score'] for r in old})
        self.assertEqual(web.decode(json.dumps(result)), result)
        with patch('joblib.load') as load:
            for key, value in [('ACCOUNTING_ANOMALY_MODEL_SHA256', '0' * 64), ('ACCOUNTING_ANOMALY_MODEL_VERSION', 'bad'), ('ACCOUNTING_ANOMALY_METADATA_PATH', str(root / 'absent.json'))]:
                bad = dict(env); bad[key] = value
                with self.assertRaises((ValueError, FileNotFoundError)): web.infer(p, bad)
            load.assert_not_called()


if __name__ == '__main__': unittest.main()
