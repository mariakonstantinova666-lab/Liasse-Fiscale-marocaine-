<?php

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * Moteur de validation de la liasse fiscale marocaine.
 *
 * Le service reste sans effet de bord : il ne modifie ni les balances, ni les
 * documents sources, ni les tableaux. Il calcule un contexte une seule fois,
 * applique des familles de regles, puis retourne des anomalies structurees.
 *
 * Compatibilite conservee : chaque resultat contient encore titre, ok, ecart,
 * message et bloquant, afin de ne pas casser les vues et tests existants.
 */
class LiasseControlService
{
    private const ERROR = 'Erreur';
    private const WARNING = 'Avertissement';
    private const INFO = 'Information';

    /** Tolerance d'arrondi en dirhams. */
    private float $tolerance = 0.5;

    /**
     * Controle historique, centre sur la balance N.
     *
     * @return array<int, array<string, mixed>>
     */
    public function verifier(Collection $items): array
    {
        return $this->verifierLiasse($items);
    }

    /**
     * Controle complet : balance N, donnees de liasse et balance N-1.
     *
     * @return array<int, array<string, mixed>>
     */
    public function verifierLiasse(Collection $items, ?Collection $liasseData = null, ?Collection $itemsPrev = null): array
    {
        if ($items->isEmpty()) {
            return [$this->regle(
                'BALANCE_EMPTY',
                'Balance importée',
                false,
                0.0,
                'Aucune ligne de balance pour cet exercice : importez une balance avant de lancer les contrôles.',
                true,
                self::ERROR,
                'Balance',
                'Balance N',
                'Une liasse EDI ne peut pas être générée sans balance de l’exercice.',
                'Importer la balance N avant de générer ou valider la liasse.'
            )];
        }

        $rows = $this->normaliser($items);
        $prevRows = $itemsPrev ? $this->normaliser($itemsPrev) : [];
        $data = $this->normaliserLiasseData($liasseData ?? collect());
        $context = $this->context($rows, $prevRows, $data);
        $hasLiasseData = $liasseData !== null;

        $regles = array_merge(
            $this->controlesComptables($context),
            $this->controlesDonnees($context, $hasLiasseData),
            $hasLiasseData ? $this->controlesCalculs($context) : [],
            $hasLiasseData ? $this->controlesFiscaux($context) : [],
            $hasLiasseData ? $this->controlesEdi($context) : []
        );

        return $this->dedoublonner($regles);
    }

    /**
     * @param array<string, mixed> $context
     * @return array<int, array<string, mixed>>
     */
    private function controlesComptables(array $context): array
    {
        $rows = $context['rows'];
        $prevRows = $context['prevRows'];
        $sumDebit = $context['sumDebit'];
        $sumCredit = $context['sumCredit'];
        $ecartBalance = $sumDebit - $sumCredit;
        $resultatCpc = $context['resultatCpc'];
        $resultat119 = $context['resultat119'];
        $soldeBilanHors119 = $context['soldeBilanHors119'];
        $resultat119Mouvemente = abs($resultat119) > $this->tolerance;

        $regles = [];

        $regles[] = $this->regle(
            'BALANCE_DEBIT_CREDIT_EQUAL',
            'Équilibre de la balance (Total débit = Total crédit)',
            abs($ecartBalance) <= $this->tolerance,
            $ecartBalance,
            sprintf('Total débit : %s ; total crédit : %s.', $this->formatMontant($sumDebit), $this->formatMontant($sumCredit)),
            true,
            self::ERROR,
            'Balance',
            'Total débit / total crédit',
            'La balance générale doit être équilibrée.',
            'Corriger l’import ou la balance comptable avant toute génération EDI.'
        );

        $resultatBilanAttendu = $resultatCpc + $resultat119;
        $ecartResultat = $resultatBilanAttendu - $soldeBilanHors119;
        $regles[] = $this->regle(
            'RESULT_CPC_BALANCE',
            'Cohérence du résultat (CPC / Bilan)',
            abs($ecartResultat) <= $this->tolerance,
            $ecartResultat,
            sprintf(
                'Résultat CPC : %s ; résultat comptabilisé au 119 : %s ; solde des classes 1 à 5 hors 119 : %s.',
                $this->formatMontant($resultatCpc),
                $this->formatMontant($resultat119),
                $this->formatMontant($soldeBilanHors119)
            ),
            true,
            self::ERROR,
            'T01/T02/T03',
            'Résultat net',
            'Le résultat du CPC doit être cohérent avec le bilan.',
            'Vérifier les comptes 6/7, les capitaux propres et le compte 119.'
        );

        if ($this->existeCompte($rows, '119')) {
            $regles[] = $this->regle(
                'RESULT_ACCOUNT_119',
                'Résultat net comptabilisé (compte 119)',
                !$resultat119Mouvemente || abs($resultatCpc - $resultat119) <= $this->tolerance,
                $resultatCpc - $resultat119,
                $resultat119Mouvemente
                    ? sprintf('Résultat CPC : %s ; montant porté au compte 119 : %s.', $this->formatMontant($resultatCpc), $this->formatMontant($resultat119))
                    : 'Le compte 119 est présent mais non mouvementé : aucun résultat net n’y est comptabilisé pour cet exercice.',
                false,
                self::WARNING,
                'T02/T03',
                'Compte 119',
                'Le compte 119 doit refléter le résultat net lorsque le résultat est comptabilisé.',
                'Contrôler le sens et le montant du compte 119.'
            );
        }

        $immobilisationsBrutes = $context['immobilisationsBrutes'];
        $amortissements = $context['amortissements'];
        $regles[] = $this->regle(
            'AMORT_LE_BRUT_IMMO',
            'Amortissements cumulés ≤ immobilisations brutes',
            $amortissements <= $immobilisationsBrutes + $this->tolerance,
            $amortissements - $immobilisationsBrutes,
            sprintf('Immobilisations brutes : %s ; amortissements cumulés : %s.', $this->formatMontant($immobilisationsBrutes), $this->formatMontant($amortissements)),
            false,
            self::WARNING,
            'T04/T08/T16',
            'Immobilisations et amortissements',
            'Les amortissements cumulés ne peuvent pas dépasser la base immobilisée brute.',
            'Vérifier les comptes 2, 28 et le registre des immobilisations.'
        );

        $dotationCpc = $this->montant($rows, ['619'], 'charge');
        $dotationT16 = $this->liasseNumber($context['data'], 'dotations_amortissements', 'total_c8');
        if ($dotationT16 !== null) {
            $regles[] = $this->regle(
                'DOTATION_T16_CPC',
                'Concordance des dotations aux amortissements (T16 / CPC)',
                abs($dotationCpc - $dotationT16) <= $this->tolerance,
                $dotationT16 - $dotationCpc,
                sprintf('Dotation CPC comptes 619 : %s ; total T16 : %s.', $this->formatMontant($dotationCpc), $this->formatMontant($dotationT16)),
                false,
                self::WARNING,
                'T16/CPC',
                'Dotations de l’exercice',
                'La dotation détaillée T16 doit concorder avec les charges d’amortissement du CPC.',
                'Rapprocher le registre des immobilisations et les comptes 619.'
            );
        }

        $regles[] = $this->controleProvisionsStocks($rows);

        $regles[] = $this->regle(
            'PREVIOUS_BALANCE_PRESENT',
            'Présence de la balance N-1',
            count($prevRows) > 0,
            count($prevRows) > 0 ? 0.0 : 1.0,
            count($prevRows) > 0
                ? 'La balance N-1 est disponible pour les colonnes exercice précédent.'
                : 'La balance N-1 est absente : les contrôles comparatifs N/N-1 seront incomplets.',
            false,
            count($prevRows) > 0 ? self::INFO : self::WARNING,
            'T01/T02/T04/T08/T20/T22',
            'Colonnes N-1',
            'Les états fiscaux comparatifs doivent pouvoir reprendre les valeurs de l’exercice précédent.',
            'Importer la balance N-1 si les tableaux comparatifs doivent être contrôlés.'
        );

        return $regles;
    }

    /**
     * @param array<string, mixed> $context
     * @return array<int, array<string, mixed>>
     */
    private function controlesDonnees(array $context, bool $withLiasseData): array
    {
        $rows = $context['rows'];
        $data = $context['data'];

        $regles = [
            $this->controleFormatComptes($rows),
            $this->controleMontantsNegatifs($rows),
            $this->controleDebitCreditSimultanes($rows),
            $this->controleComptesDupliques($rows),
            $this->controlePresenceCpc($context['charges'], $context['produits']),
            $this->controleSensAmortissements($rows),
            $this->controleTva($rows),
        ];

        if ($withLiasseData) {
            $regles[] = $this->controleChampsObligatoires($data);
            $regles[] = $this->controleFormatsLiasse($data);
        }

        return $regles;
    }

    /**
     * @param array<string, mixed> $context
     * @return array<int, array<string, mixed>>
     */
    private function controlesCalculs(array $context): array
    {
        $data = $context['data'];
        $regles = [];

        $t13Capital = $this->liasseNumber($data, 'repartition_capital', 'montant_capital');
        $t13Total = $this->liasseNumber($data, 'repartition_capital', 'total_c10');
        if ($t13Capital !== null && $t13Total !== null) {
            $regles[] = $this->regle(
                'T13_CAPITAL_TOTAL',
                'Total du capital social (T13)',
                abs($t13Capital - $t13Total) <= $this->tolerance,
                $t13Total - $t13Capital,
                sprintf('Montant du capital : %s ; total souscrit : %s.', $this->formatMontant($t13Capital), $this->formatMontant($t13Total)),
                false,
                self::WARNING,
                'T13',
                'Capital social',
                'Le total du capital souscrit doit correspondre au capital social déclaré.',
                'Corriger les lignes d’associés ou le montant du capital.'
            );
        }

        $t14A = $this->liasseNumber($data, 'affectation_resultats', 'total_A');
        $t14B = $this->liasseNumber($data, 'affectation_resultats', 'total_B');
        if ($t14A !== null && $t14B !== null) {
            $regles[] = $this->regle(
                'T14_TOTAL_A_EQUALS_B',
                'Affectation des résultats : Total A = Total B',
                abs($t14A - $t14B) <= $this->tolerance,
                $t14A - $t14B,
                sprintf('Total A : %s ; total B : %s.', $this->formatMontant($t14A), $this->formatMontant($t14B)),
                true,
                self::ERROR,
                'T14',
                'Total A / Total B',
                'L’origine et l’affectation des résultats doivent être équilibrées.',
                'Reprendre les montants de report à nouveau, dividendes et résultat affecté.'
            );
        }

        $t16TotalC3 = $this->liasseNumber($data, 'dotations_amortissements', 'total_c3');
        $t16TotalC4 = $this->liasseNumber($data, 'dotations_amortissements', 'total_c4');
        if ($t16TotalC3 !== null && $t16TotalC4 !== null) {
            $regles[] = $this->regle(
                'T16_PRIX_EQUALS_REEVAL',
                'T16 : prix d’acquisition et valeur après réévaluation',
                abs($t16TotalC3 - $t16TotalC4) <= $this->tolerance,
                $t16TotalC4 - $t16TotalC3,
                sprintf('Total prix acquisition : %s ; total valeur réévaluée : %s.', $this->formatMontant($t16TotalC3), $this->formatMontant($t16TotalC4)),
                false,
                self::WARNING,
                'T16',
                'Colonnes 2 et 3',
                'En absence de réévaluation, la valeur comptable après réévaluation doit reprendre le prix d’acquisition.',
                'Compléter la colonne valeur réévaluée ou justifier la réévaluation.'
            );
        }

        $regles[] = $this->controleTotalT16($data);

        return $regles;
    }

    /**
     * @param array<string, mixed> $context
     * @return array<int, array<string, mixed>>
     */
    private function controlesFiscaux(array $context): array
    {
        $data = $context['data'];
        $resultatComptable = $context['resultatCpc'];
        $benefice = max(0.0, $resultatComptable);
        $perte = max(0.0, -$resultatComptable);
        $reintCourantes = $this->liasseNumber($data, 'passage_fiscal', 'reintegrations_courantes_total') ?? 0.0;
        $reintNonCourantes = $this->liasseNumber($data, 'passage_fiscal', 'reintegrations_non_courantes_total') ?? 0.0;
        $dedCourantes = $this->liasseNumber($data, 'passage_fiscal', 'deductions_courantes_total') ?? 0.0;
        $dedNonCourantes = $this->liasseNumber($data, 'passage_fiscal', 'deductions_non_courantes_total') ?? 0.0;
        $reports = $this->liasseNumber($data, 'passage_fiscal', 'reports_deficitaires_total') ?? 0.0;
        $resultatFiscalCalcule = $benefice - $perte + $reintCourantes + $reintNonCourantes - $dedCourantes - $dedNonCourantes - $reports;

        return [
            $this->regle(
                'T03_FISCAL_RESULT_FORMULA',
                'Cohérence résultat comptable / résultat fiscal (T03)',
                true,
                0.0,
                sprintf(
                    'Résultat comptable : %s ; réintégrations : %s ; déductions : %s ; reports imputés : %s ; résultat fiscal calculé : %s.',
                    $this->formatMontant($resultatComptable),
                    $this->formatMontant($reintCourantes + $reintNonCourantes),
                    $this->formatMontant($dedCourantes + $dedNonCourantes),
                    $this->formatMontant($reports),
                    $this->formatMontant($resultatFiscalCalcule)
                ),
                false,
                self::INFO,
                'T03',
                'Résultat fiscal',
                'Le résultat fiscal est recalculé à partir du résultat comptable et des retraitements.',
                'Comparer ce montant avec la ligne T03 avant génération EDI.'
            ),
            $this->regle(
                'T03_REINTEGRATION_DETAILS',
                'Détail des réintégrations fiscales (T03)',
                ($reintCourantes + $reintNonCourantes) >= -$this->tolerance,
                $reintCourantes + $reintNonCourantes,
                sprintf('Réintégrations courantes : %s ; non courantes : %s.', $this->formatMontant($reintCourantes), $this->formatMontant($reintNonCourantes)),
                false,
                self::WARNING,
                'T03',
                'Réintégrations fiscales',
                'Les retraitements fiscaux doivent être positifs ou nuls.',
                'Vérifier la feuille Règles fiscales des documents sources.'
            ),
        ];
    }

    /**
     * @param array<string, mixed> $context
     * @return array<int, array<string, mixed>>
     */
    private function controlesEdi(array $context): array
    {
        $data = $context['data'];
        $requiredTables = ['passage_fiscal', 'repartition_capital', 'affectation_resultats'];
        $missing = array_values(array_filter($requiredTables, fn ($table) => empty($data[$table])));

        return [
            $this->regle(
                'EDI_REQUIRED_TABLES_READY',
                'Préparation EDI : tableaux déclaratifs essentiels',
                count($missing) === 0,
                (float) count($missing),
                count($missing) === 0
                    ? 'Les tableaux déclaratifs essentiels disposent de données de contrôle.'
                    : sprintf('Tableaux sans données préparées : %s.', implode(', ', $missing)),
                count($missing) > 0,
                count($missing) === 0 ? self::INFO : self::ERROR,
                'EDI',
                'Tables obligatoires',
                'Le fichier XML sera rejeté si des blocs obligatoires sont absents ou vides.',
                'Importer les documents sources ou compléter les tableaux avant génération XML.'
            ),
        ];
    }

    /**
     * @param array<int, array{compte:string, debit:float, credit:float}> $rows
     * @param array<int, array{compte:string, debit:float, credit:float}> $prevRows
     * @param array<string, array<string, string>> $data
     * @return array<string, mixed>
     */
    private function context(array $rows, array $prevRows, array $data): array
    {
        $charges = $this->montant($rows, ['6'], 'charge');
        $produits = $this->montant($rows, ['7'], 'produit');

        return [
            'rows' => $rows,
            'prevRows' => $prevRows,
            'data' => $data,
            'sumDebit' => $this->somme($rows, fn () => true, 'debit'),
            'sumCredit' => $this->somme($rows, fn () => true, 'credit'),
            'charges' => $charges,
            'produits' => $produits,
            'resultatCpc' => $produits - $charges,
            'soldeBilanHors119' => $this->solde($rows, ['1', '2', '3', '4', '5'], ['119']),
            'resultat119' => $this->montant($rows, ['119'], 'produit'),
            'immobilisationsBrutes' => $this->solde($rows, ['2'], ['28', '29']),
            'amortissements' => $this->montant($rows, ['28'], 'produit'),
        ];
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
     * @return array<string, array<string, string>>
     */
    private function normaliserLiasseData(Collection $items): array
    {
        $data = [];

        foreach ($items as $item) {
            $data[(string) $item->tableau_code][(string) $item->cle] = (string) $item->valeur;
        }

        return $data;
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
            'ACCOUNT_FORMAT',
            'Format des numéros de comptes',
            count($invalides) === 0,
            (float) count($invalides),
            count($invalides) === 0
                ? 'Tous les numéros de comptes sont numériques et suffisamment détaillés.'
                : sprintf('%d compte(s) avec un format anormal : %s.', count($invalides), $this->listeComptes($invalides)),
            false,
            self::WARNING,
            'Balance',
            'Numéros de comptes',
            'Les comptes doivent être numériques et suffisamment détaillés pour les mappings fiscaux.',
            'Corriger les numéros de comptes dans le fichier balance.'
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
            'BALANCE_NEGATIVE_AMOUNTS',
            'Montants négatifs dans la balance',
            count($invalides) === 0,
            (float) count($invalides),
            count($invalides) === 0
                ? 'Aucun solde débiteur ou créditeur négatif détecté.'
                : sprintf('%d ligne(s) contiennent un montant négatif : %s.', count($invalides), $this->listeComptes($invalides)),
            false,
            self::WARNING,
            'Balance',
            'Soldes',
            'Les colonnes débit/crédit ne doivent pas contenir de montants négatifs.',
            'Présenter le montant dans la colonne opposée au lieu de saisir un signe négatif.'
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
            'BALANCE_DOUBLE_SIDED_LINE',
            'Débit et crédit simultanés sur une même ligne',
            count($invalides) === 0,
            (float) count($invalides),
            count($invalides) === 0
                ? 'Chaque compte est présenté sur un seul côté de solde.'
                : sprintf('%d compte(s) ont à la fois un débit et un crédit : %s.', count($invalides), $this->listeComptes($invalides)),
            false,
            self::WARNING,
            'Balance',
            'Débit / Crédit',
            'Une ligne de balance doit porter un solde net sur un seul côté.',
            'Nettoyer la balance et ne garder qu’un solde débiteur ou créditeur par compte.'
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
            'BALANCE_DUPLICATED_ACCOUNTS',
            'Comptes dupliqués dans la balance',
            count($dupliques) === 0,
            (float) count($dupliques),
            count($dupliques) === 0
                ? 'Aucun numéro de compte n’est répété plusieurs fois.'
                : sprintf('%d compte(s) apparaissent plusieurs fois : %s.', count($dupliques), implode(', ', array_slice($dupliques, 0, 8))),
            false,
            self::WARNING,
            'Balance',
            'Comptes',
            'Les doublons peuvent fausser les totaux et les états fiscaux.',
            'Regrouper les mouvements par compte avant import.'
        );
    }

    private function controlePresenceCpc(float $charges, float $produits): array
    {
        $ok = abs($charges) > $this->tolerance || abs($produits) > $this->tolerance;

        return $this->regle(
            'CPC_ACCOUNTS_PRESENT',
            'Présence de comptes CPC (classes 6 et 7)',
            $ok,
            $produits - $charges,
            $ok
                ? sprintf('Charges classe 6 : %s ; produits classe 7 : %s.', $this->formatMontant($charges), $this->formatMontant($produits))
                : 'Aucun compte de charge ou de produit n’est présent : le CPC ne pourra pas être contrôlé correctement.',
            false,
            $ok ? self::INFO : self::WARNING,
            'CPC',
            'Classes 6 et 7',
            'Le CPC et le passage fiscal nécessitent des comptes de charges ou de produits.',
            'Importer une balance complète incluant les classes 6 et 7.'
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
            'AMORT_ACCOUNT_DIRECTION',
            'Sens des comptes d’amortissement (28)',
            count($debitAnormal) === 0,
            (float) count($debitAnormal),
            count($debitAnormal) === 0
                ? 'Les comptes 28 présentent un solde créditeur ou nul, conforme à leur nature.'
                : sprintf('%d compte(s) 28 présentent un solde débiteur anormal : %s.', count($debitAnormal), $this->listeComptes($debitAnormal)),
            false,
            self::WARNING,
            'T08/T16',
            'Comptes 28',
            'Les amortissements cumulés ont normalement un solde créditeur.',
            'Vérifier le sens des comptes 28 dans la balance.'
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
            'STOCK_PROVISIONS_LE_STOCKS',
            'Provisions pour dépréciation des stocks',
            $ecart <= $this->tolerance,
            $ecart,
            sprintf('Stocks bruts classe 31 : %s ; provisions 391 : %s.', $this->formatMontant($stocks), $this->formatMontant($provisionsStocks)),
            false,
            self::WARNING,
            'T09/T20',
            'Stocks et provisions',
            'Les provisions de stocks ne doivent pas dépasser les stocks bruts.',
            'Contrôler les comptes 31 et 391.'
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
            'VAT_ACCOUNT_DIRECTION',
            'Sens des soldes TVA',
            $ok,
            min($tvaRecuperable, $tvaFacturee),
            sprintf('TVA récupérable 3455 : %s ; TVA facturée 4455 : %s.', $this->formatMontant($tvaRecuperable), $this->formatMontant($tvaFacturee)),
            false,
            self::WARNING,
            'T12',
            'TVA',
            'Les soldes TVA doivent être portés dans le sens attendu.',
            'Vérifier les comptes 3455 et 4455.'
        );
    }

    /**
     * @param array<string, array<string, string>> $data
     */
    private function controleChampsObligatoires(array $data): array
    {
        $required = [
            'repartition_capital' => ['montant_capital'],
            'affectation_resultats' => ['total_A', 'total_B'],
            'passage_fiscal' => ['reintegrations_courantes_total', 'reintegrations_non_courantes_total'],
        ];
        $missing = [];

        foreach ($required as $table => $keys) {
            foreach ($keys as $key) {
                if (($data[$table][$key] ?? '') === '') {
                    $missing[] = $table.'.'.$key;
                }
            }
        }

        return $this->regle(
            'LIASSE_REQUIRED_FIELDS',
            'Champs obligatoires de la liasse',
            count($missing) === 0,
            (float) count($missing),
            count($missing) === 0
                ? 'Les champs obligatoires contrôlés sont renseignés.'
                : sprintf('Champs manquants : %s.', implode(', ', array_slice($missing, 0, 10))),
            count($missing) > 0,
            count($missing) === 0 ? self::INFO : self::ERROR,
            'Liasse',
            'Champs obligatoires',
            'Certains blocs EDI ne peuvent pas être vides.',
            'Compléter les tableaux indiqués ou réimporter le dossier fiscal source.'
        );
    }

    /**
     * @param array<string, array<string, string>> $data
     */
    private function controleFormatsLiasse(array $data): array
    {
        $invalides = [];

        foreach ($data as $table => $fields) {
            foreach ($fields as $key => $value) {
                if ($this->looksAmountKey($table, $key) && !$this->isNumericValue($value)) {
                    $invalides[] = $table.'.'.$key;
                }
                if ($this->looksDateKey($table, $key) && !$this->isDateValue($value)) {
                    $invalides[] = $table.'.'.$key;
                }
            }
        }

        return $this->regle(
            'LIASSE_FIELD_FORMATS',
            'Formats des champs de liasse',
            count($invalides) === 0,
            (float) count($invalides),
            count($invalides) === 0
                ? 'Les montants et dates contrôlés ont un format exploitable.'
                : sprintf('Formats incorrects : %s.', implode(', ', array_slice($invalides, 0, 10))),
            false,
            self::WARNING,
            'Liasse',
            'Formats',
            'Les montants et dates doivent être convertibles avant génération XML.',
            'Corriger les champs saisis ou relancer l’extraction du document source.'
        );
    }

    /**
     * @param array<string, array<string, string>> $data
     */
    private function controleTotalT16(array $data): array
    {
        $total = $this->liasseNumber($data, 'dotations_amortissements', 'total_c8');
        $sum = 0.0;
        $hasRows = false;

        for ($i = 0; $i < 10; $i++) {
            $value = $this->liasseNumber($data, 'dotations_amortissements', 'r'.$i.'_c8');
            if ($value !== null) {
                $sum += $value;
                $hasRows = true;
            }
        }

        if (!$hasRows || $total === null) {
            return $this->regle(
                'T16_DOTATION_TOTAL',
                'T16 : total des dotations',
                true,
                0.0,
                'Aucune ligne T16 détaillée disponible pour recalculer le total.',
                false,
                self::INFO,
                'T16',
                'Total dotations',
                'Le total T16 sera contrôlé dès que des lignes détaillées seront présentes.',
                'Importer ou compléter le registre des immobilisations.'
            );
        }

        return $this->regle(
            'T16_DOTATION_TOTAL',
            'T16 : total des dotations',
            abs($sum - $total) <= $this->tolerance,
            $sum - $total,
            sprintf('Somme des lignes : %s ; total déclaré : %s.', $this->formatMontant($sum), $this->formatMontant($total)),
            false,
            self::WARNING,
            'T16',
            'Total dotations',
            'Le total de la colonne dotations doit correspondre à la somme des lignes.',
            'Corriger les lignes T16 ou le total.'
        );
    }

    /**
     * @param array<string, array<string, string>> $data
     */
    private function liasseNumber(array $data, string $table, string $key): ?float
    {
        if (!isset($data[$table][$key]) || $data[$table][$key] === '') {
            return null;
        }

        $value = str_replace(["\xc2\xa0", ' ', '%'], '', (string) $data[$table][$key]);
        $value = str_replace(',', '.', $value);

        return is_numeric($value) ? (float) $value : null;
    }

    private function looksAmountKey(string $table, string $key): bool
    {
        if (str_contains($key, 'label') || str_contains($key, 'date')) {
            return false;
        }

        if (in_array($table, ['passage_fiscal', 'affectation_resultats'], true)) {
            return str_contains($key, 'montant') || str_contains($key, 'total');
        }

        if ($table === 'repartition_capital') {
            return $key === 'montant_capital'
                || preg_match('/^(r\d+|total)_c(7|8|9|10|11|12)$/', $key) === 1;
        }

        if ($table === 'dotations_amortissements') {
            return $key === 'montant_global'
                || preg_match('/^(r\d+|total)_c(3|4|5|8|9)$/', $key) === 1;
        }

        if ($table === 'locations_baux') {
            return preg_match('/^(r\d+|total)_c(10|11)$/', $key) === 1;
        }

        return str_contains($key, 'montant') || str_contains($key, 'total');
    }

    private function looksDateKey(string $table, string $key): bool
    {
        return str_contains($key, 'date')
            || ($table === 'dotations_amortissements' && preg_match('/^r\d+_c2$/', $key) === 1)
            || ($table === 'locations_baux' && preg_match('/^r\d+_c9$/', $key) === 1);
    }

    private function isNumericValue(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return true;
        }

        $value = str_replace(["\xc2\xa0", ' ', '%'], '', $value);
        $value = str_replace(',', '.', $value);

        return is_numeric($value);
    }

    private function isDateValue(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return true;
        }

        return preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $value) === 1
            || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1;
    }

    /**
     * @param array<int, array<string, mixed>> $regles
     * @return array<int, array<string, mixed>>
     */
    private function dedoublonner(array $regles): array
    {
        $seen = [];

        return array_values(array_filter($regles, function ($regle) use (&$seen) {
            $key = (string) ($regle['id'] ?? $regle['titre']);
            if (isset($seen[$key])) {
                return false;
            }

            $seen[$key] = true;

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
            . (count(array_unique($comptes)) > 8 ? '...' : '');
    }

    private function formatMontant(float $value): string
    {
        return number_format($value, 2, ',', ' ');
    }

    private function regle(
        string $id,
        string $titre,
        bool $ok,
        float $ecart,
        string $message,
        bool $bloquant,
        string $severity,
        string $tableau,
        string $rubrique,
        string $regle,
        string $suggestion
    ): array {
        return [
            'id' => $id,
            'titre' => $titre,
            'ok' => $ok,
            'ecart' => $ecart,
            'message' => $message,
            'bloquant' => $bloquant,
            'severity' => $severity,
            'niveau' => $severity,
            'tableau' => $tableau,
            'cellule' => $rubrique,
            'rubrique' => $rubrique,
            'description' => $message,
            'regle' => $regle,
            'suggestion' => $suggestion,
        ];
    }
}
