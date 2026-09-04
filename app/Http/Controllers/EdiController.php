<?php

namespace App\Http\Controllers;

use App\Services\EdiGenerationException;
use App\Services\EdiXmlGeneratorService;
use App\Services\ActiveExerciceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EdiController extends Controller
{
    public function index(
        EdiXmlGeneratorService $edi,
        ActiveExerciceService $activeExercice
    ): \Illuminate\View\View
    {
        $exercice = $activeExercice->current();
        $context = $edi->context(Auth::id(), $exercice);
        $controles = $context['controls'];
        $bloquants = collect($controles)->filter(fn ($rule) => $rule['bloquant'] && !$rule['ok'])->values();
        $erreursGeneration = collect(session('edi_blocking_errors', []))->values();
        $bloquantsAffiches = $erreursGeneration->isNotEmpty()
            ? $erreursGeneration
            : $bloquants;
        $avertissements = collect($controles)->filter(fn ($rule) => !$rule['ok'] && !$rule['bloquant'])->count();

        return view('liasse.edi', [
            'exercice' => $exercice,
            'societe' => $context['societe'],
            'controles' => $controles,
            'bloquants' => $bloquants,
            'bloquantsAffiches' => $bloquantsAffiches,
            'erreursGeneration' => $erreursGeneration,
            'avertissements' => $avertissements,
            'nombreChamps' => $context['liasseData']->count(),
            'nombreLignesBalance' => $context['items']->count(),
            'nombreLignesBalancePrecedente' => $context['itemsPrev']->count(),
        ]);
    }

    public function generate(
        EdiXmlGeneratorService $edi,
        ActiveExerciceService $activeExercice
    ): BinaryFileResponse|RedirectResponse
    {
        $exercice = $activeExercice->current();

        try {
            $result = $edi->generate(Auth::id(), $exercice);
        } catch (EdiGenerationException $exception) {
            return redirect()
                ->route('liasse.edi.index')
                ->with('error', $exception->getMessage())
                ->with('edi_blocking_errors', $exception->blockingErrors());
        }

        return response()
            ->download($result['path'], $result['filename'], ['Content-Type' => 'application/xml'])
            ->deleteFileAfterSend(false);
    }
}
