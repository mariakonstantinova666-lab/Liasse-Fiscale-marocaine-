<?php

namespace App\Services;

use App\Models\BalanceItem;
use App\Models\ActiveExercicePreference;
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

        $societeId = $this->societeId();
        $preference = $societeId !== null && $this->tableExists('active_exercice_preferences')
            ? ActiveExercicePreference::where('user_id', Auth::id())
                ->where('societe_id', $societeId)->value('exercice')
            : null;

        if ($preference !== null && in_array((int) $preference, $available, true)) {
            session(['annee_exercice' => (int) $preference]);

            return (int) $preference;
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

        $societeId = $this->societeId();

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

        $societeId = $this->societeId();
        if ($societeId !== null && $this->tableExists('active_exercice_preferences')) {
            ActiveExercicePreference::updateOrCreate([
                'user_id' => Auth::id(),
                'societe_id' => $societeId,
            ], ['exercice' => $exercice]);
        }

        session(['annee_exercice' => $exercice]);
    }

    private function societeId(): ?int
    {
        if (Auth::id() === null || !$this->tableExists('societes')) {
            return null;
        }

        $id = Societe::where('user_id', Auth::id())->value('id');

        return $id === null ? null : (int) $id;
    }

    protected function tableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }
}
