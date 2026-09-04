<?php

namespace App\Http\Controllers;

use App\Services\ActiveExerciceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExerciceController extends Controller
{
    public function select(Request $request, ActiveExerciceService $activeExercice): RedirectResponse
    {
        $validated = $request->validate([
            'exercice' => ['required', 'integer'],
        ]);

        $activeExercice->select((int) $validated['exercice']);

        return redirect()->back(fallback: route('dashboard'));
    }
}
