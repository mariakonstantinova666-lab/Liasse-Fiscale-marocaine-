<?php

namespace App\Http\Controllers;

use App\Models\FiscalExercise;
use App\Models\Societe;
use App\Services\ActiveExerciceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ExerciceController extends Controller
{
    public function store(Request $request, ActiveExerciceService $activeExercice): RedirectResponse
    {
        $validated = $request->validate([
            'exercice' => ['required', 'integer', 'min:1900', 'max:'.(now()->year + 10)],
        ]);

        $societes = Societe::query()
            ->where('user_id', $request->user()->id)
            ->get();

        if ($societes->count() !== 1) {
            throw ValidationException::withMessages([
                'exercice' => 'La création d’un exercice exige exactement une société configurée pour ce compte.',
            ]);
        }

        $exercice = (int) $validated['exercice'];
        $fiscalExercise = FiscalExercise::firstOrCreate([
            'user_id' => $request->user()->id,
            'societe_id' => $societes->first()->id,
            'exercice' => $exercice,
        ]);

        $activeExercice->select($exercice);

        return redirect()
            ->route('settings.index')
            ->with('success', $fiscalExercise->wasRecentlyCreated
                ? "L’exercice fiscal {$exercice} a été créé et activé."
                : "L’exercice fiscal {$exercice} existe déjà et a été activé.");
    }

    public function select(Request $request, ActiveExerciceService $activeExercice): RedirectResponse
    {
        $validated = $request->validate([
            'exercice' => ['required', 'integer'],
        ]);

        $activeExercice->select((int) $validated['exercice']);

        return redirect()->back(fallback: route('dashboard'));
    }
}
