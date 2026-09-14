"""Local experiment. Threshold uses validation only; test never selects anything."""
import argparse
import hashlib
from datetime import datetime, timezone
from pathlib import Path

import joblib
import numpy as np
import sklearn
from sklearn.ensemble import IsolationForest
from sklearn.metrics import confusion_matrix, precision_score, recall_score, f1_score

from common import FEATURES, PROTOCOL, flatten, load_json, matrix, validate_dataset, write_json


def metrics(labels, alerts, rows):
    tn, fp, fn, tp = confusion_matrix(labels, alerts, labels=[0, 1]).ravel()
    counterpart = np.array([r["counterpart_effect"] for r in rows])
    return dict(precision=float(precision_score(labels, alerts, zero_division=0)),
                recall=float(recall_score(labels, alerts, zero_division=0)),
                f1=float(f1_score(labels, alerts, zero_division=0)),
                confusion=[[int(tn), int(fp)], [int(fn), int(tp)]],
                false_positives=int(fp), false_negatives=int(fn), alerts=int(sum(alerts)),
                counterpart_alerts=int(sum(alerts & counterpart)))


def threshold(scores, rows, budget=None):
    labels = np.array([r["targeted_anomaly"] for r in rows], dtype=int)
    near = np.array([r["scenario_type"] == "synthetic_near_normal" for r in rows])
    candidates = np.r_[np.nextafter(scores.min(), -np.inf), np.unique(scores)]
    choices = []
    for t in candidates:
        alerts = scores <= t
        if budget is None and np.mean(alerts[near]) > .05:
            continue
        m = metrics(labels, alerts, rows)
        key = (-abs(m["alerts"] - budget), m["f1"], -m["false_positives"]) if budget is not None else (m["f1"], -m["false_positives"], -m["alerts"])
        choices.append((key, float(t)))
    return max(choices, key=lambda x: x[0])[1]


def describe_test(scenarios, alerts, original):
    reference = {r['account']: r for r in original}
    families, categories, historical, accounts = {}, dict(counterpart=0, normalization_only=0, unchanged_features=0), 0, {}
    i = 0
    for scenario in scenarios:
        family = scenario['operation_type']
        stats = families.setdefault(family, dict(scenarios=0, targets=0, detected=0, false_positives=0))
        stats['scenarios'] += 1
        for row in scenario['observations']:
            alert = bool(alerts[i]); i += 1
            stats['targets'] += int(row['targeted_anomaly'])
            stats['detected'] += int(alert and row['targeted_anomaly'])
            if alert and not row['targeted_anomaly']:
                stats['false_positives'] += 1
                category = 'counterpart' if row['counterpart_effect'] else ('normalization_only' if row['features'] != reference[row['account']]['features'] else 'unchanged_features')
                categories[category] += 1
                historical += int(abs(reference[row['account']]['features']['symmetric_change']) >= .5)
                accounts[row['account']] = accounts.get(row['account'], 0) + 1
    for stats in families.values():
        stats['recall'] = stats['detected'] / stats['targets'] if stats['targets'] else 0.0
    return dict(families=families, false_positive_categories=categories,
                historical_large_variation_overlay=historical, false_positive_accounts=accounts)


def run(data, output, dataset_hash):
    validate_dataset(data)
    train, validation, test = [flatten(data[k]) for k in ("train", "validation", "test")]
    xt, xv, xe = [matrix(r) for r in (train, validation, test)]
    yv = np.array([r["targeted_anomaly"] for r in validation], dtype=int)
    ye = np.array([r["targeted_anomaly"] for r in test], dtype=int)
    stability, reference = [], None
    final = None
    for seed in (42, 43, 44, 45, 46):
        model = IsolationForest(n_estimators=200, max_samples="auto", contamination="auto", random_state=seed).fit(xt)
        cutoff = threshold(model.score_samples(xv), validation)
        scores = model.score_samples(xe)
        ranks = np.argsort(np.argsort(scores, kind="stable"), kind="stable")
        if reference is None: reference = ranks
        stability.append(dict(seed=seed, rank_correlation=float(np.corrcoef(reference, ranks)[0, 1]),
                              metrics=metrics(ye, scores <= cutoff, test), threshold=cutoff))
        if seed == 42: final = model, cutoff
    model, cutoff = final
    v_scores, e_scores = model.score_samples(xv), model.score_samples(xe)
    vm = metrics(yv, v_scores <= cutoff, validation)
    # Negative baseline scores preserve the same '<= threshold' convention.
    bv, be = -abs(xv[:, 0]), -abs(xe[:, 0])
    baseline_cutoff = threshold(bv, validation, vm["alerts"])
    report = dict(protocol=PROTOCOL, dataset_hash=dataset_hash,
                  sizes={k: dict(scenarios=len(data[k]), observations=len(flatten(data[k]))) for k in ("train", "validation", "test")},
                  threshold=cutoff, baseline_threshold=baseline_cutoff,
                  validation=dict(isolation_forest=vm, baseline=metrics(yv, bv <= baseline_cutoff, validation)),
                  test=dict(isolation_forest=metrics(ye, e_scores <= cutoff, test), baseline=metrics(ye, be <= baseline_cutoff, test)),
                  stability=stability)
    report['description'] = describe_test(data['test'], e_scores <= cutoff, data['train'][0]['observations'])
    output = Path(output)
    output.mkdir(parents=True, exist_ok=True)
    artifact = dict(model=model, threshold=cutoff, feature_order=FEATURES, protocol=PROTOCOL,
                    sklearn_version=sklearn.__version__, numpy_version=np.__version__, joblib_version=joblib.__version__,
                    random_state=42, trained_at=datetime.now(timezone.utc).isoformat(), dataset_hash=dataset_hash)
    if (output / "model.joblib").exists(): raise ValueError("Refusing to overwrite an existing experiment")
    joblib.dump(artifact, output / "model.joblib")
    write_json(output / "metadata.json", {k: v for k, v in artifact.items() if k != "model"})
    write_json(output / "evaluation.json", report)
    return report


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("dataset")
    parser.add_argument("output")
    args = parser.parse_args()
    print(run(load_json(args.dataset), args.output, hashlib.sha256(Path(args.dataset).read_bytes()).hexdigest()))
