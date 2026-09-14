"""Load ONLY locally created trusted joblib artifacts. Never user-supplied models."""
import argparse
import json
import math
import joblib
import sklearn
from common import FEATURES, PROTOCOL, load_json, matrix, write_json


def predict(artifact, rows):
    if artifact["feature_order"] != FEATURES or artifact["protocol"] != PROTOCOL or artifact["sklearn_version"] != sklearn.__version__:
        raise ValueError("Incompatible artifact")
    if not isinstance(artifact["threshold"], (int, float)) or not math.isfinite(artifact["threshold"]):
        raise ValueError("Finite threshold required")
    x = matrix(rows)
    model = artifact["model"]
    scores, decisions = model.score_samples(x), model.decision_function(x)
    return [dict(account=r["account"], label=r.get("label"), absolute_change=r.get("absolute_change"),
                 relative_change=r.get("relative_change"), features=r["features"],
                 score=float(s), decision_score=float(d), is_atypical=bool(s <= artifact["threshold"]))
            for r, s, d in zip(rows, scores, decisions)]


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("model")
    parser.add_argument("input")
    parser.add_argument("output")
    args = parser.parse_args()
    write_json(args.output, predict(joblib.load(args.model), load_json(args.input)))
