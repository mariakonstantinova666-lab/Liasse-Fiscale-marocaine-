# Local experimental Isolation Forest

No web integration, business writes or fiscal decisions. PHP owns all feature formulas.
Install Python 3.12 and `requirements.txt` in `.venv`. Artifacts and JSON live only in
`storage/app/private/ml/accounting_anomaly/<run>/`, never `public/`.

1. `php ml/export.php experiment USER SOCIETE RUN`
2. `.venv/Scripts/python.exe ml/train.py DATASET PRIVATE_RUN_DIRECTORY`
3. Only after training: `php ml/export.php holdout USER SOCIETE RUN`
4. `.venv/Scripts/python.exe ml/predict.py MODEL HOLDOUT PRIVATE_RESULT_JSON`

Train: original 2024->2025 plus 10 near scenarios. Validation and test: each 10 near
and 10 injected scenarios. Split by scenario, deduplicated by feature content.
All descendants share one original history: no claim of independent enterprises.
No 2026 feature is read during training or threshold selection.

Threshold: maximize validation account-level F1 subject to <=5% alerts on near
scenarios. Ties prefer fewer false positives, then fewer alerts. This 5% is an
experimental budget, not a fiscal standard. Baseline uses abs(symmetric_change),
choosing the closest attainable validation alert count (ties are inseparable).
Test metrics use targeted injected accounts only; counterpart alerts are separate.
Seeds 42..46 measure stability, not optimization; final seed remains 42.

Lower score_samples / decision_function means more atypical. The saved custom
threshold applies to score_samples, not the estimator's default decision threshold.
Scores are not probabilities. Descriptive features are not causal explanations.

SECURITY: joblib can execute code. NEVER load an uploaded/user-provided artifact.
Only locally generated trusted models with matching pinned dependencies are allowed.
The protocol is exploratory and neither production-validated nor fraud detection.

## Phase 3B predeclared enrichment
Run name prefix `phase3b_` selects five families, six near scenarios per family
in each split and six injected scenarios per family in validation/test. No 2026
input enters this choice. Features, estimator, threshold rule and baseline remain
unchanged. Expense/revenue/equipment amplitudes: near 1..5%, injected 50..100%.
Supplier settlement: 1..5% or 50..80% of min(bank balance, supplier debt).
Customer collection: same ranges of the receivable. Remaining balances never
cross zero. Equipment: office/computer 2351/2355 debit against supplier credit;
Single-family batches use 0.01 percentage-point resolution to avoid exhausting
five integer amplitudes on the single client/bank pair; ranges are unchanged.
net-of-tax simulation only, no tax/amortization accounting claim.
Stocks rejected: observed finished-goods 315 and in-progress variation 71312 do
not establish a matching pair. Provisions rejected: no explicit provision/dotation
pair. No account 119 balancing. These are simulations, not certified business events.
Comparison changes both family mix and sample count, so causal attribution to
diversity alone is not possible. FP historical overlay uses abs(symmetric_change)
>=0.5 as a descriptive marker only, not a tuned detector.
