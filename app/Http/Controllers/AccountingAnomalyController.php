<?php

namespace App\Http\Controllers;

use App\Models\BalanceItem;
use App\Models\Societe;
use App\Services\AccountingAnomalyFeatureService;
use App\Services\AccountingAnomalyInferenceService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountingAnomalyController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->page($request, false);
    }

    public function analyze(Request $request): Response
    {
        return $this->page($request, true);
    }

    private function page(Request $request, bool $analyze): Response
    {
        $year = $request->session()->get('annee_exercice');
        $year = (is_int($year) || (is_string($year) && preg_match('/^[0-9]{4}$/D', $year))) ? (int) $year : null;
        $societes = Societe::query()->where('user_id', $request->user()->id)->limit(2)->get();
        $message = null; $count = null; $analysis = null;
        if ($societes->isEmpty()) { $message = 'Analyse IA indisponible : aucune société disponible.'; }
        elseif ($societes->count() !== 1) { $message = 'Analyse IA indisponible : plusieurs sociétés nécessitent une sélection explicite.'; }
        elseif ($year === null || $year < 1001 || $year > 9999 || !in_array($year, app(\App\Services\ActiveExerciceService::class)->available(), true)) {
            $message = 'Analyse IA indisponible : aucun exercice actif valide.';
        } else {
            $query = BalanceItem::query()->where('user_id', $request->user()->id)->where('societe_id', $societes->sole()->id);
            $count = (clone $query)->where('exercice', $year)->count();
            if (!$count || !(clone $query)->where('exercice', $year - 1)->exists()) {
                $message = 'Analyse IA indisponible : une balance pour N et N-1 est nécessaire.';
            }
        }
        if ($message === null && !config('accounting_anomaly.enabled')) {
            $message = 'Le module d’analyse IA n’est pas activé sur ce serveur.';
        }
        if ($analyze && $message === null) {
            try {
                $observations = app(AccountingAnomalyFeatureService::class)->buildTransition($request->user()->id, $societes->sole()->id, $year - 1, $year);
            } catch (\Throwable) {
                $message = 'Analyse IA indisponible : les balances ne sont pas compatibles avec le protocole.';
            }
            if ($message === null) {
                try {
                    $analysis = app(AccountingAnomalyInferenceService::class)->infer($observations, $year - 1, $year);
                    $labels = array_column($observations, 'label', 'account');
                    usort($analysis['results'], fn ($a, $b) => ($a['score_samples'] <=> $b['score_samples']) ?: strcmp($a['account'], $b['account']));
                    foreach ($analysis['results'] as $i => &$row) { $row['rank'] = $i + 1; $row['label'] = $labels[$row['account']]; }
                    unset($row);
                    $analysis['flagged_count'] = count(array_filter($analysis['results'], fn ($row) => $row['flagged']));
                } catch (\Throwable $exception) {
                    $message = match ($exception->getMessage()) {
                        'IA_DISABLED' => 'Le module d’analyse IA n’est pas activé sur ce serveur.',
                        'IA_PYTHON_UNAVAILABLE' => 'Le moteur d’analyse IA est indisponible.',
                        'IA_TIMEOUT' => 'L’analyse IA a dépassé le temps autorisé.',
                        'IA_MODEL_UNAVAILABLE', 'IA_METADATA_UNAVAILABLE', 'IA_ARTIFACT_LOCATION', 'IA_FINGERPRINT', 'IA_METADATA_INVALID' => 'Le modèle expérimental est indisponible.',
                        default => 'L’analyse IA n’a pas pu produire un résultat exploitable.',
                    };
                    $analysis = null;
                }
            }
        }
        // Override shared lazy current(): this module must not normalize the session.
        return Inertia::render('AccountingAnomaly/Index', ['activeExercice' => $year, 'exercise' => $year,
            'previousExercise' => $year ? $year - 1 : null, 'accountsCount' => $count,
            'modelVersion' => config('accounting_anomaly.model_version'), 'unavailable' => $message, 'analysis' => $analysis]);
    }
}
