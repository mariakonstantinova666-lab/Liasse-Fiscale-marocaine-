"""Descriptive comparison only; never selects model parameters or thresholds."""
import argparse
from collections import Counter
from pathlib import Path
import joblib
import numpy as np
from common import flatten, load_json, matrix, write_json
from train import describe_test


def compare(old_path, new_path):
    old, new = Path(old_path), Path(new_path)
    # Both models must already be frozen before any holdout file is opened.
    for path in (old, new):
        if not (path / 'model.joblib').exists() or not (path / 'metadata.json').exists():
            raise ValueError('Frozen model required')
    old_data = load_json(old / 'dataset.json')
    old_model = joblib.load(old / 'model.joblib')
    old_test = flatten(old_data['test'])
    old_description = describe_test(old_data['test'], old_model['model'].score_samples(matrix(old_test)) <= old_model['threshold'],
                                    old_data['train'][0]['observations'])
    old_scores, new_scores = load_json(old / 'holdout_scores.json'), load_json(new / 'holdout_scores.json')
    old_order = sorted(old_scores, key=lambda x: x['score'])
    new_order = sorted(new_scores, key=lambda x: x['score'])
    old_ranks = {r['account']: i + 1 for i, r in enumerate(old_order)}
    ranks = [dict(row, old_rank=old_ranks[row['account']], new_rank=i + 1) for i, row in enumerate(new_order)]
    data = load_json(new / 'dataset.json')
    distribution = {}
    for split in ('train', 'validation', 'test'):
        groups = {}
        for s in data[split]:
            key = s['operation_type'] + ':' + s['scenario_type']
            g = groups.setdefault(key, dict(scenarios=0, targets=set(), counterparts=set(), classes=set()))
            g['scenarios'] += 1
            g['targets'].update(s.get('targeted_accounts', []))
            g['counterparts'].update(s.get('counterpart_accounts', []))
            g['classes'].update(a[0] for a in s.get('modified_accounts', []))
        distribution[split] = {k: {a: sorted(v) if isinstance(v, set) else v for a, v in g.items()} for k, g in groups.items()}
    report = dict(old_description=old_description, distribution=distribution,
                  old_alerts=sum(r['is_atypical'] for r in old_scores), new_alerts=sum(r['is_atypical'] for r in new_scores),
                  old_top10=[r['account'] for r in old_order[:10]], new_top10=ranks[:10])
    write_json(new / 'comparison.json', report)
    return report


if __name__ == '__main__':
    p = argparse.ArgumentParser(); p.add_argument('old'); p.add_argument('new'); a = p.parse_args()
    print(compare(a.old, a.new))
