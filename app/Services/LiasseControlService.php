<?php

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * Moteur de contrôle de cohérence de la liasse.
 *
 * Le service reste volontairement centré sur la balance importée : il ne
 * recalcule pas les tableaux, ne modifie pas les imports et ne persiste rien.
 * Chaque règle renvoie : titre, ok, écart, message et bloquant.
 */
class LiasseControlService
{
    /** Tolérance d'arrondi en dirhams. */
    private float $tolerance = 0.5;

    /**
     * @return array<int, array{titre:string, ok:bool, ecart:float, message:string, bloquant:bool}>
     */
    public function verifier(Collection $items): array
    {
        if ($items->isEmpty()) {
            return [$this->regle(
                'Balance importée',
                false,
                0.0,
                'Aucune ligne de balance pour cet exercice : importez une balance avant de lancer les contrôles.',
                true
            )];
        }

        $rows = $this->normaliser($items);
        $regles = [];

        $sumDebit = $this->somme($rows, fn () => true, 'debit');
        $sumCredit = $this->somme($rows, fn () => true, 'credit');
        $ecartBalance = $sumDebit - $sumCredit;

        $charges = $this->montant($rows, ['6'], 'charge');
        $produits = $this->montant($rows, ['7'], 'produit');
        $resultatCpc = $produits - $charges;

        $soldeBilanHors119 = $this->solde($rows, ['1', '2', '3', '4', '5'], ['119']);
        $resultat119 = $this->montant($rows, ['119'], 'produit');
        $resultat119Mouvemente = abs($resultat119) > $this->tolerance;

        // Règle historique 1 : équilibre débit/crédit.
        $regles[] = $this->regle(
            'Équilibre de la balance (Total débit = Total crédit)',
            abs($ecartBalance) <= $this->tolerance,
            $ecartBalance,
            sprintf(
                'Total débit : %s ; total crédit : %s. La balance doit être équilibrée avant génération de la liasse.',
                $this->formatMontant($sumDebit),
                $this->formatMontant($sumCredit)
            ),
            true
        );

        // Règle historique 2 : cohérence résultat CPC / comptes de bilan.
        // Si 119 est mouvementé, le bilan hors 119 doit intégrer ce résultat
        // comptabilisé : solde bilan hors 119 = résultat CPC + résultat 119.
        $resultatBilanAttendu = $resultatCpc + $resultat119;
        $ecartResultat = $resultatBilanAttendu - $soldeBilanHors119;
        $regles[] = $this->regle(
            'Cohérence du résultat (CPC / Bilan)',
            abs($ecartResultat) <= $this->tolerance,
            $ecartResultat,
            sprintf(
                'Résultat CPC : %s ; résultat comptabilisé au 119 : %s ; solde des classes 1 à 5 hors 119 : %s.',
                $this->formatMontant($resultatCpc),
                $this->formatMontant($resultat119),
                $this->formatMontant($soldeBilanHors119)
            ),
            true
        );

        // Règle historique 3 : résultat net comptabilisé, seulement si 119 est
        // réellement mouvementé. Une ligne 119 à zéro ne doit pas créer une
        // fausse anomalie.
        if ($this->existeCompte($rows, '119')) {
            $regles[] = $this->regle(
                'Résultat net comptabilisé (compte 119)',
                !$resultat119Mouvemente || abs($resultatCpc - $resultat119) <= $this->tolerance,
                $resultatCpc - $resultat119,
                $resultat119Mouvemente
                    ? sprintf(
                        'Résultat CPC : %s ; montant porté au compte 119 : %s.',
                        $this->formatMontant($resultatCpc),
                        $this->formatMontant($resultat119)
                    )
                    : 'Le compte 119 est présent mais non mouvementé : aucun résultat net n’y est comptabilisé pour cet exercice.',
                false
            );
        }

        // Règle historique 4 : les amortissements ne doivent pas excéder les
        // immobilisations brutes correspondantes.
        $immobilisationsBrutes = $this->solde($rows, ['2'], ['28', '29']);
        $amortissements = $this->montant($rows, ['28'], 'produit');
        $regles[] = $this->regle(
            'Amortissements cumulés ≤ immobilisations brutes',
            $amortissements <= $immobilisationsBrutes + $this->tolerance,
            $amortissements - $immobilisationsBrutes,
            sprintf(
                'Immobilisations brutes : %s ; amortissements cumulés : %s.',
                $this->formatMontant($immobilisationsBrutes),
                $this->formatMontant($amortissements)
            ),
            false
        );

        // Règle historique 5 : racine comptable reconnue.
        $horsPlan = array_values(array_filter($rows, fn ($row) => !$this->classeValide($row['compte'])));
        $regles[] = $this->regle(
            'Comptes rattachés à une classe valide (1 à 9)',
            count($horsPlan) === 0,
            (float) count($horsPlan),
            count($horsPlan) === 0
                ? 'Tous les comptes appartiennent à une classe du plan comptable marocain.'
                : sprintf(
                    '%d compte(s) avec une classe non reconnue : %s.',
                    count($horsPlan),
                    $this->listeComptes($horsPlan)
                ),
            false
        );

        // Nouveaux contrôles de qualité de balance.
        $regles[] = $this->controleFormatComptes($rows);
        $regles[] = $this->controleMontantsNegatifs($rows);
        $regles[] = $this->controleDebitCreditSimultanes($rows);
        $regles[] = $this->controleComptesDupliques($rows);
        $regles[] = $this->controlePresenceCpc($charges, $produits);
        $regles[] = $this->controleSensAmortissements($rows);
        $regles[] = $this->controleProvisionsStocks($rows);
        $regles[] = $this->controleTva($rows);

        return $this->dedoublonner($regles);
    }

    /**
     * @return array<int, array{compte:string, debit:float, credit:float}>
     */
    private function normaliser(Collection $items): array
    {
        return $items->map(fn ($item) => [
            'compte' => trim((string) $item->compte),
            'debit' => (float) $item->solde_debiteur,
            'credit' => (float) $item->solde_crediteur,
        ])->all();
    }

    /**
     * @param array<int, array{compte:string, debit:float, credit:float}> $rows
     * @param callable(array{compte:string, debit:float, credit:float}):bool $predicate
     */
    private function somme(array $rows, callable $predicate, string $column): float
    {
        $total = 0.0;

        foreach ($rows as $row) {
            if ($predicate($row)) {
                $total += $row[$column];
            }
        }

        return $total;
    }

    /**
     * Solde comptable débit - crédit.
     *
     * @param array<int, array{compte:string, debit:float, credit:float}> $rows
     * @param array<int, string> $prefixes
     * @param array<int, string> $excludePrefixes
     */
    private function solde(array $rows, array $prefixes, array $excludePrefixes = []): float
    {
        $total = 0.0;

        foreach ($rows as $row) {
            if ($this->matches($row['compte'], $prefixes) && !$this->matches($row['compte'], $excludePrefixes)) {
                $total += $row['debit'] - $row['credit'];
            }
        }

        return $total;
    }

    /**
     * Montant signé selon la nature du poste.
     *
     * @param array<int, array{compte:string, debit:float, credit:float}> $rows
     * @param array<int, string> $prefixes
     */
    private function montant(array $rows, array $prefixes, string $type): float
    {
        $solde = $this->solde($rows, $prefixes);

        return $type === 'produit' ? -$solde : $solde;
    }

    /**
     * @param array<int, string> $prefixes
     */
    private function matches(string $compte, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($compte, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, array{compte:string, debit:float, credit:float}> $rows
     */
    private function existeCompte(array $rows, string $prefix): bool
    {
        foreach ($rows as $row) {
            if (str_starts_with($row['compte'], $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function classeValide(string $compte): bool
    {
        $first = substr(ltrim($compte), 0, 1);

        return in_array($first, ['1', '2', '3', '4', '5', '6', '7', '8', '9'], true);
    }

    /**
     * @param array<int, array{compte:string, debit:float, credit:float}> $rows
     */
    private function controleFormatComptes(array $rows): array
    {
        $invalides = array_values(array_filter($rows, fn ($row) =>
            $row['compte'] === ''
            || !preg_match('/^\d+$/', $row['compte'])
            || strlen($row['compte']) < 3
        ));

        return $this->regle(
            'Format des numéros de comptes',
            count($invalides) === 0,
            (float) count($invalides),
            count($invalides) === 0
                ? 'Tous les numéros de comptes sont numériques et suffisamment détaillés.'
                : sprintf('%d compte(s) avec un format anormal : %s.', count($invalides), $this->listeComptes($invalides)),
            false
        );
    }

    /**
     * @param array<int, array{compte:string, debit:float, credit:float}> $rows
     */
    private function controleMontantsNegatifs(array $rows): array
    {
        $invalides = array_values(array_filter($rows, fn ($row) =>
            $row['debit'] < -$this->tolerance || $row['credit'] < -$this->tolerance
        ));

        return $this->regle(
            'Montants négatifs dans la balance',
            count($invalides) === 0,
            (float) count($invalides),
            count($invalides) === 0
                ? 'Aucun solde débiteur ou créditeur négatif détecté.'
                : sprintf('%d ligne(s) contiennent un montant négatif : %s.', count($invalides), $this->listeComptes($invalides)),
            false
        );
    }

    /**
     * @param array<int, array{compte:string, debit:float, credit:float}> $rows
     */
    private function controleDebitCreditSimultanes(array $rows): array
    {
        $invalides = array_values(array_filter($rows, fn ($row) =>
            $row['debit'] > $this->tolerance && $row['credit'] > $this->tolerance
        ));

        return $this->regle(
            'Débit et crédit simultanés sur une même ligne',
            count($invalides) === 0,
            (float) count($invalides),
            count($invalides) === 0
                ? 'Chaque compte est présenté sur un seul côté de solde.'
                : sprintf('%d compte(s) ont à la fois un débit et un crédit : %s.', count($invalides), $this->listeComptes($invalides)),
            false
        );
    }

    /**
     * @param array<int, array{compte:string, debit:float, credit:float}> $rows
     */
    private function controleComptesDupliques(array $rows): array
    {
        $counts = [];

        foreach ($rows as $row) {
            if ($row['compte'] === '') {
                continue;
            }

            $counts[$row['compte']] = ($counts[$row['compte']] ?? 0) + 1;
        }

        $dupliques = array_keys(array_filter($counts, fn ($count) => $count > 1));

        return $this->regle(
            'Comptes dupliqués dans la balance',
            count($dupliques) === 0,
            (float) count($dupliques),
            count($dupliques) === 0
                ? 'Aucun numéro de compte n’est répété plusieurs fois.'
                : sprintf('%d compte(s) apparaissent plusieurs fois : %s.', count($dupliques), implode(', ', array_slice($dupliques, 0, 8))),
            false
        );
    }

    private function controlePresenceCpc(float $charges, float $produits): array
    {
        $ok = abs($charges) > $this->tolerance || abs($produits) > $this->tolerance;

        return $this->regle(
            'Présence de comptes CPC (classes 6 et 7)',
            $ok,
            $produits - $charges,
            $ok
                ? sprintf('Charges classe 6 : %s ; produits classe 7 : %s.', $this->formatMontant($charges), $this->formatMontant($produits))
                : 'Aucun compte de charge ou de produit n’est présent : le CPC ne pourra pas être contrôlé correctement.',
            false
        );
    }

    /**
     * @param array<int, array{compte:string, debit:float, credit:float}> $rows
     */
    private function controleSensAmortissements(array $rows): array
    {
        $debitAnormal = array_values(array_filter($rows, fn ($row) =>
            str_starts_with($row['compte'], '28') && $row['debit'] > $row['credit'] + $this->tolerance
        ));

        return $this->regle(
            'Sens des comptes d’amortissement (28)',
            count($debitAnormal) === 0,
            (float) count($debitAnormal),
            count($debitAnormal) === 0
                ? 'Les comptes 28 présentent un solde créditeur ou nul, conforme à leur nature.'
                : sprintf('%d compte(s) 28 présentent un solde débiteur anormal : %s.', count($debitAnormal), $this->listeComptes($debitAnormal)),
            false
        );
    }

    /**
     * @param array<int, array{compte:string, debit:float, credit:float}> $rows
     */
    private function controleProvisionsStocks(array $rows): array
    {
        $stocks = max(0.0, $this->solde($rows, ['31']));
        $provisionsStocks = max(0.0, $this->montant($rows, ['391'], 'produit'));
        $ecart = $provisionsStocks - $stocks;

        return $this->regle(
            'Provisions pour dépréciation des stocks',
            $ecart <= $this->tolerance,
            $ecart,
            sprintf(
                'Stocks bruts classe 31 : %s ; provisions 391 : %s.',
                $this->formatMontant($stocks),
                $this->formatMontant($provisionsStocks)
            ),
            false
        );
    }

    /**
     * @param array<int, array{compte:string, debit:float, credit:float}> $rows
     */
    private function controleTva(array $rows): array
    {
        $tvaRecuperable = $this->solde($rows, ['3455']);
        $tvaFacturee = $this->montant($rows, ['4455'], 'produit');
        $ok = $tvaRecuperable >= -$this->tolerance && $tvaFacturee >= -$this->tolerance;

        return $this->regle(
            'Sens des soldes TVA',
            $ok,
            min($tvaRecuperable, $tvaFacturee),
            sprintf(
                'TVA récupérable 3455 : %s ; TVA facturée 4455 : %s.',
                $this->formatMontant($tvaRecuperable),
                $this->formatMontant($tvaFacturee)
            ),
            false
        );
    }

    /**
     * @param array<int, array{titre:string, ok:bool, ecart:float, message:string, bloquant:bool}> $regles
     * @return array<int, array{titre:string, ok:bool, ecart:float, message:string, bloquant:bool}>
     */
    private function dedoublonner(array $regles): array
    {
        $seen = [];

        return array_values(array_filter($regles, function ($regle) use (&$seen) {
            if (isset($seen[$regle['titre']])) {
                return false;
            }

            $seen[$regle['titre']] = true;

            return true;
        }));
    }

    /**
     * @param array<int, array{compte:string, debit:float, credit:float}> $rows
     */
    private function listeComptes(array $rows): string
    {
        $comptes = array_map(fn ($row) => $row['compte'] !== '' ? $row['compte'] : '(vide)', $rows);

        return implode(', ', array_slice(array_unique($comptes), 0, 8))
            . (count(array_unique($comptes)) > 8 ? '…' : '');
    }

    private function formatMontant(float $value): string
    {
        return number_format($value, 2, ',', ' ');
    }

    private function regle(string $titre, bool $ok, float $ecart, string $message, bool $bloquant): array
    {
        return compact('titre', 'ok', 'ecart', 'message', 'bloquant');
    }
}
