<?php

namespace App\Services;

use App\Models\BalanceItem;
use Illuminate\Support\Collection;

/**
 * Service central de lecture des balances comptables.
 *
 * Concentre ici toute la logique d'accès aux balances N et N-1 afin que les
 * contrôleurs (Balance, Liasse) et les futurs tableaux n'aient plus à
 * dupliquer les requêtes. Toutes les requêtes passent par l'ORM Eloquent
 * (requêtes préparées => aucune injection SQL possible).
 */
class BalanceService
{
    /**
     * Lignes de balance d'un exercice donné pour un utilisateur.
     */
    public function lignesExercice(int $userId, int $exercice): Collection
    {
        return BalanceItem::query()
            ->where('user_id', $userId)
            ->where('exercice', $exercice)
            ->get();
    }

    /**
     * Couple [N, N-1] des lignes de balance, prêt à alimenter un tableau fiscal
     * et sa colonne "Exercice Précédent".
     *
     * @return array{0: Collection, 1: Collection}
     */
    public function lignesAvecPrecedent(int $userId, int $exercice): array
    {
        return [
            $this->lignesExercice($userId, $exercice),
            $this->lignesExercice($userId, $exercice - 1),
        ];
    }

    /**
     * Liste des exercices déjà importés pour une société, du plus récent au
     * plus ancien. Alimente le bandeau d'import N / N-1 du tableau de bord.
     *
     * @return int[]
     */
    public function exercicesImportes(int $societeId): array
    {
        return BalanceItem::query()
            ->where('societe_id', $societeId)
            ->distinct()
            ->orderByDesc('exercice')
            ->pluck('exercice')
            ->map(fn ($annee) => (int) $annee)
            ->all();
    }

    /**
     * Vrai si une balance est déjà importée pour cette société et cet exercice.
     */
    public function aImporte(int $societeId, int $exercice): bool
    {
        return BalanceItem::query()
            ->where('societe_id', $societeId)
            ->where('exercice', $exercice)
            ->exists();
    }
}
