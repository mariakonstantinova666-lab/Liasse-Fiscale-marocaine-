<?php

namespace App\Services;

use App\Models\BalanceItem;
use App\Models\FiscalExercise;
use App\Models\Societe;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ActiveExerciceService
{
    public function current(): int
    {
        if (!$this->tableExists('fiscal_exercises')) {
            return session()->has('annee_exercice')
                ? (int) session('annee_exercice')
                : now()->year;
        }

        $available = $this->available();
        $sessionExercice = session()->has('annee_exercice')
            ? (int) session('annee_exercice')
            : null;

        if ($sessionExercice !== null && in_array($sessionExercice, $available, true)) {
            return $sessionExercice;
        }

        $exercice = $available[0] ?? null;

        if ($exercice === null) {
            session()->forget('annee_exercice');

            return now()->year;
        }

        session(['annee_exercice' => $exercice]);

        return $exercice;
    }

    /** @return int[] */
    public function available(): array
    {
        if (!$this->tableExists('societes')) {
            return [];
        }

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

        $fiscalExercices = $this->tableExists('fiscal_exercises')
            ? FiscalExercise::query()
                ->where('user_id', $userId)
                ->where('societe_id', $societeId)
                ->pluck('exercice')
            : collect();

        $balanceExercices = $this->tableExists('balance_items')
            ? BalanceItem::query()
                ->where('user_id', $userId)
                ->where('societe_id', $societeId)
                ->distinct()
                ->pluck('exercice')
            : collect();

        return $fiscalExercices
            ->merge($balanceExercices)
            ->map(fn ($exercice) => (int) $exercice)
            ->unique()
            ->sortDesc()
            ->values()
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

    protected function tableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }
}
