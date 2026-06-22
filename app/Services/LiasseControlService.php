<?php

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * Moteur de contrôle de cohérence de la liasse.
 *
 * Exécute des règles comptables et arithmétiques inter-tableaux à partir de la
 * balance importée. Chaque règle renvoie : titre, ok (bool), écart, message,
 * bloquant (bool). Les règles bloquantes empêchent la validation finale.
 */
class LiasseControlService
{
    /** Tolérance d'arrondi (en DH). */
    private float $tolerance = 0.5;

    /**
     * @return array<int, array{titre:string, ok:bool, ecart:float, message:string, bloquant:bool}>
     */
    public function verifier(Collection $items): array
    {
        $regles = [];

        if ($items->isEmpty()) {
            return [$this->regle(
                'Balance importée',
                false,
                0.0,
                "Aucune ligne de balance pour cet exercice : importez une balance pour lancer les contrôles.",
                true
            )];
        }

        // --- Données de base ---
        $sumDebit  = (float) $items->sum(fn ($i) => (float) $i->solde_debiteur);
        $sumCredit = (float) $items->sum(fn ($i) => (float) $i->solde_crediteur);

        $charges  = $this->classe($items, '6', 'charge');   // débit - crédit
        $produits = $this->classe($items, '7', 'produit');  // crédit - débit
        $resultatCPC = $produits - $charges;

        // Comptes de bilan (classes 1 à 5) hors compte de résultat 119
        $bilan = $items->filter(fn ($i) =>
            in_array(substr((string) $i->compte, 0, 1), ['1', '2', '3', '4', '5'], true)
            && !str_starts_with((string) $i->compte, '119'));
        $variationBilan = (float) $bilan->sum(fn ($i) => (float) $i->solde_debiteur - (float) $i->solde_crediteur);

        // Compte de résultat net (119x) s'il est mouvementé
        $resultat119 = (float) $items->filter(fn ($i) => str_starts_with((string) $i->compte, '119'))
            ->sum(fn ($i) => (float) $i->solde_crediteur - (float) $i->solde_debiteur);

        // --- Règle 1 : équilibre Débit / Crédit (Actif = Passif) ---
        $regles[] = $this->regle(
            'Équilibre de la balance (Total Débit = Total Crédit)',
            abs($sumDebit - $sumCredit) <= $this->tolerance,
            $sumDebit - $sumCredit,
            'La somme des soldes débiteurs doit être strictement égale à la somme des soldes créditeurs.',
            true
        );

        // --- Règle 2 : cohérence du résultat (CPC vs Bilan) ---
        $regles[] = $this->regle(
            'Cohérence du résultat (CPC / Bilan)',
            abs($resultatCPC - $variationBilan) <= $this->tolerance,
            $resultatCPC - $variationBilan,
            'Le résultat du CPC (Produits − Charges) doit correspondre à la variation des comptes de bilan.',
            true
        );

        // --- Règle 3 : résultat net comptabilisé (compte 119) ---
        if ($items->contains(fn ($i) => str_starts_with((string) $i->compte, '119'))) {
            $regles[] = $this->regle(
                'Résultat net comptabilisé (compte 119)',
                abs($resultatCPC - $resultat119) <= $this->tolerance,
                $resultatCPC - $resultat119,
                'Le résultat porté au compte 119 doit être égal au résultat dégagé par le CPC.',
                false
            );
        }

        // --- Règle 4 : cohérence des amortissements (28xx) vs immobilisations (2xx) ---
        $immobBrut = (float) $items->filter(fn ($i) =>
            in_array(substr((string) $i->compte, 0, 1), ['2'], true)
            && !str_starts_with((string) $i->compte, '28')
            && !str_starts_with((string) $i->compte, '29'))
            ->sum(fn ($i) => (float) $i->solde_debiteur - (float) $i->solde_crediteur);
        $amort = (float) $items->filter(fn ($i) => str_starts_with((string) $i->compte, '28'))
            ->sum(fn ($i) => (float) $i->solde_crediteur - (float) $i->solde_debiteur);
        $regles[] = $this->regle(
            'Amortissements cumulés ≤ Immobilisations brutes',
            $amort <= $immobBrut + $this->tolerance,
            $amort - $immobBrut,
            'Le cumul des amortissements (comptes 28) ne peut pas dépasser la valeur brute des immobilisations.',
            false
        );

        // --- Règle 5 : comptes rattachés à une classe valide ---
        $horsPlan = $items->filter(fn ($i) =>
            !in_array(substr(ltrim((string) $i->compte), 0, 1), ['1', '2', '3', '4', '5', '6', '7', '8', '9'], true));
        $regles[] = $this->regle(
            'Comptes rattachés à une classe valide (1 à 9)',
            $horsPlan->isEmpty(),
            (float) $horsPlan->count(),
            $horsPlan->isEmpty()
                ? 'Tous les comptes appartiennent à une classe du plan comptable marocain.'
                : $horsPlan->count() . ' compte(s) avec une racine non reconnue.',
            false
        );

        return $regles;
    }

    private function classe(Collection $items, string $prefix, string $type): float
    {
        return (float) $items->filter(fn ($i) => str_starts_with((string) $i->compte, $prefix))
            ->sum(fn ($i) => $type === 'produit'
                ? (float) $i->solde_crediteur - (float) $i->solde_debiteur
                : (float) $i->solde_debiteur - (float) $i->solde_crediteur);
    }

    private function regle(string $titre, bool $ok, float $ecart, string $message, bool $bloquant): array
    {
        return compact('titre', 'ok', 'ecart', 'message', 'bloquant');
    }
}
