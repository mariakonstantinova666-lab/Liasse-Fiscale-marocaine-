<?php

namespace App\Services;

use App\Models\FiscalExercise;
use App\Models\Societe;
use RuntimeException;

class FiscalExerciseResolver
{
    public function resolve(int $userId, int $exercice, ?int $societeId = null): FiscalExercise
    {
        $societe = $societeId === null
            ? $this->resolveOnlySociete($userId)
            : Societe::query()
                ->whereKey($societeId)
                ->where('user_id', $userId)
                ->first();

        if ($societe === null) {
            throw new RuntimeException('La société fournie n’appartient pas à cet utilisateur.');
        }

        $fiscalExercise = FiscalExercise::query()
            ->where('user_id', $userId)
            ->where('societe_id', $societe->id)
            ->where('exercice', $exercice)
            ->first();

        if ($fiscalExercise === null) {
            throw new RuntimeException("Aucun exercice fiscal {$exercice} n’existe pour cette société.");
        }

        return $fiscalExercise;
    }

    private function resolveOnlySociete(int $userId): Societe
    {
        $societes = Societe::query()
            ->where('user_id', $userId)
            ->get();

        if ($societes->count() !== 1) {
            throw new RuntimeException(
                'La résolution implicite exige exactement une société appartenant à cet utilisateur.'
            );
        }

        return $societes->first();
    }
}
