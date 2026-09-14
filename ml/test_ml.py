import copy
import tempfile
import unittest
from pathlib import Path
import joblib
import numpy as np
import sklearn
from sklearn.ensemble import IsolationForest
from common import FEATURES, PROTOCOL, load_json, matrix, validate_dataset
from predict import predict
from train import threshold, metrics, describe_test


class ContractTests(unittest.TestCase):
    def test_threshold_budget_baseline_metrics_and_descriptive_families(self):
        rows = [dict(account=str(i), targeted_anomaly=i == 4, counterpart_effect=i == 3,
                     scenario_type='synthetic_near_normal' if i < 3 else 'synthetic_anomaly',
                     features=dict(zip(FEATURES, [0.0] * 5))) for i in range(5)]
        scores = np.array([-.2, -.3, -.4, -.7, -.8])
        cutoff = threshold(scores, rows)
        self.assertEqual(cutoff, -.8)
        alerts = scores <= cutoff
        result = metrics(np.array([0, 0, 0, 0, 1]), alerts, rows)
        self.assertEqual(result['confusion'], [[4, 0], [0, 1]])
        baseline = threshold(scores, rows, 1)
        self.assertEqual(int(sum(scores <= baseline)), 1)
        description = describe_test([dict(operation_type='fixture', observations=rows)], alerts, rows)
        self.assertEqual(description['families']['fixture']['detected'], 1)
    def row(self):
        return dict(account="6131", features=dict(zip(FEATURES, [.1, .2, .3, .4, .5])))

    def test_feature_order_and_numeric_validation(self):
        r = self.row()
        self.assertEqual(matrix([r]).shape, (1, 5))
        for v in (float('nan'), float('inf'), True, '1', None):
            bad = copy.deepcopy(r); bad['features'][FEATURES[0]] = v
            with self.assertRaises(ValueError): matrix([bad])
        for keys in (FEATURES[:-1], FEATURES + ['extra'], FEATURES[::-1]):
            with self.assertRaises(ValueError): matrix([dict(account='1', features=dict.fromkeys(keys, 0))])

    def test_invalid_json(self):
        with tempfile.TemporaryDirectory() as d:
            p = Path(d) / 'bad.json'
            for text in ('{', '{"x":NaN}'):
                p.write_text(text)
                with self.assertRaises(ValueError): load_json(p)

    def test_reproducibility_and_trusted_reload(self):
        x = np.random.default_rng(42).normal(size=(50, 5))
        a = IsolationForest(n_estimators=200, random_state=42).fit(x)
        b = IsolationForest(n_estimators=200, random_state=42).fit(x)
        np.testing.assert_array_equal(a.score_samples(x), b.score_samples(x))
        artifact = dict(model=a, threshold=-.5, feature_order=FEATURES, protocol=PROTOCOL, sklearn_version=sklearn.__version__)
        rows = [self.row()]
        with tempfile.TemporaryDirectory() as d:
            p = Path(d) / 'local.joblib'; joblib.dump(artifact, p)
            loaded = joblib.load(p)
            self.assertEqual(loaded['threshold'], -.5)
            self.assertEqual(predict(artifact, rows), predict(loaded, rows))
            loaded['feature_order'] = FEATURES[::-1]
            with self.assertRaises(ValueError): predict(loaded, rows)

    def test_split_holdout_anomaly_and_content_leakage(self):
        data = {k: [] for k in ('train', 'validation', 'test')}
        for i, (split, kind) in enumerate([('train', 'original'), ('train', 'synthetic_near_normal'),
                                         ('validation', 'synthetic_near_normal'), ('validation', 'synthetic_anomaly'),
                                         ('test', 'synthetic_near_normal'), ('test', 'synthetic_anomaly')]):
            row = self.row()
            row.update(scenario_id=str(i), scenario_type=kind, previous_year=2024, current_year=2025,
                       targeted_anomaly=kind == 'synthetic_anomaly', counterpart_effect=False,
                       synthetic_label=None if kind == 'original' else int(kind == 'synthetic_anomaly'))
            row['features'][FEATURES[0]] = i / 10
            data[split].append(dict(scenario_id=str(i), scenario_type=kind, observations=[row]))
        validate_dataset(data)
        bad = copy.deepcopy(data); bad['validation'][0]['observations'][0]['current_year'] = 2026
        with self.assertRaises(ValueError): validate_dataset(bad)
        bad = copy.deepcopy(data); bad['test'][0] = copy.deepcopy(bad['train'][1])
        with self.assertRaises(ValueError): validate_dataset(bad)
        bad = copy.deepcopy(data); bad['train'].append(copy.deepcopy(bad['test'][-1]))
        with self.assertRaises(ValueError): validate_dataset(bad)


if __name__ == '__main__': unittest.main()
