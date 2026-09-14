"""Strict contract for PHP-calculated features; no feature formulas here."""
import hashlib
import json
import math
from pathlib import Path

import numpy as np

FEATURES = ["symmetric_change", "class_scaled_change", "share_class_current",
            "share_class_change", "share_total_change"]
PROTOCOL = "account-transition-v1"


def load_json(path):
    return json.loads(Path(path).read_text(encoding="utf-8-sig"),
                      parse_constant=lambda value: (_ for _ in ()).throw(ValueError(value)))


def write_json(path, data):
    Path(path).write_text(json.dumps(data, ensure_ascii=False, indent=2, allow_nan=False), encoding="utf-8")


def matrix(rows):
    if not isinstance(rows, list) or not rows:
        raise ValueError("Nonempty observation list required")
    result = []
    for row in rows:
        features = row.get("features", {})
        if list(features) != FEATURES:
            raise ValueError("Exact feature names and order required")
        values = list(features.values())
        if any(isinstance(v, bool) or not isinstance(v, (int, float)) or not math.isfinite(v) for v in values):
            raise ValueError("Finite numeric features required")
        result.append(values)
    return np.asarray(result, dtype=float)


def validate_dataset(data):
    ids, fingerprints = set(), set()
    for split in ("train", "validation", "test"):
        if not data.get(split):
            raise ValueError("Missing split")
        for scenario in data[split]:
            sid = scenario["scenario_id"]
            if sid in ids:
                raise ValueError("Scenario leakage")
            ids.add(sid)
            rows = scenario["observations"]
            matrix(rows)
            fp = hashlib.sha256(json.dumps([(r["account"], r["features"]) for r in rows], sort_keys=True).encode()).hexdigest()
            if fp in fingerprints:
                raise ValueError("Duplicate scenario content across dataset")
            fingerprints.add(fp)
            kind = scenario["scenario_type"]
            if kind not in ("original", "synthetic_near_normal", "synthetic_anomaly"):
                raise ValueError("Invalid origin")
            if split == "train" and kind == "synthetic_anomaly":
                raise ValueError("Anomaly in training")
            if split != "train" and kind == "original":
                raise ValueError("Original data outside train")
            for row in rows:
                if row["previous_year"] != 2024 or row["current_year"] != 2025 or row["scenario_id"] != sid:
                    raise ValueError("Holdout/context leakage")
                if type(row["targeted_anomaly"]) is not bool or type(row["counterpart_effect"]) is not bool:
                    raise ValueError("Boolean labels required")
                expected = None if kind == "original" else int(row["targeted_anomaly"])
                if row["synthetic_label"] != expected or (row["targeted_anomaly"] and row["counterpart_effect"]):
                    raise ValueError("Invalid experimental label")
    return data


def flatten(scenarios):
    return [row for scenario in scenarios for row in scenario["observations"]]
