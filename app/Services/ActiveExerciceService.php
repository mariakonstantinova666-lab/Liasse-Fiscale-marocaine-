<?php

namespace App\Services;

use App\Models\BalanceItem;
use App\Models\Societe;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ActiveExerciceService
{
    public function current(): int
    {
        if (session()->has('annee_exercice')) {
            return (int) session('annee_exercice');
        }

        $exercice = $this->available()[0] ?? null;

        if ($exercice === null) {
            return now()->year;
        }

        session(['annee_exercice' => $exercice]);

        return $exercice;
    }

    /** @return int[] */
    public function available(): array
    {
        $userId = Auth::id();
        if ($userId === null) {
            return [];
        }

        $societeId = Societe::query()
            ->where('user_id', $userId)
            ->value('id');

        if ($societeId === null) {
            return [];
        }

        return BalanceItem::query()
            ->where('user_id', $userId)
            ->where('societe_id', $societeId)
            ->distinct()
            ->orderByDesc('exercice')
            ->pluck('exercice')
            ->map(fn ($exercice) => (int) $exercice)
            ->all();
    }

    public function select(int $exercice): void
    {
        if (!in_array($exercice, $this->available(), true)) {
            throw ValidationException::withMessages([
                'exercice' => "L'exercice sélectionné n'est pas disponible pour cette société.",
            ]);
        }

        session(['annee_exercice' => $exercice]);
    }
}
