<?php

namespace App\Presenters;

/** Display labels only: never changes extraction keys or values. */
final class SourceDocumentFieldPresenter
{
    private const TABLES = [
        'passage_fiscal' => 'T03 — Passage du résultat comptable au résultat fiscal',
        'credit_bail' => 'T07 — Crédit-bail',
        'plus_values' => 'T10 — Plus ou moins-values',
        'titres_participation' => 'T11 — Titres de participation',
        'repartition_capital' => 'T13 — Répartition du capital',
        'affectation_resultats' => 'T14 — Affectation des résultats',
        'dotations_amortissements' => 'T16 — Dotations aux amortissements',
        'locations_baux' => 'T19 — Locations et baux',
        'methodes_evaluation' => 'T23 — Méthodes d’évaluation',
        'derogations' => 'T24 — Dérogations',
        'changements_methodes' => 'T25 — Changements de méthodes',
    ];

    private const CAPITAL_COLUMNS = [
        '1' => "Nom et prénom de l'associé",
        '2' => "Raison sociale de l'associé",
        '3' => 'Identifiant fiscal',
        '4' => 'CIN',
        '5' => "Carte d'étranger",
        '6' => 'Adresse',
        '7' => 'Nombre de titres — Exercice précédent',
        '8' => 'Nombre de titres — Exercice actuel',
        '9' => 'Valeur nominale de chaque action ou part sociale',
        '10' => 'Capital souscrit',
        '11' => 'Capital appelé',
        '12' => 'Capital libéré',
    ];

    // Labels verified against resources/views/liasse/{tableau_code}.blade.php.
    private const ROW_COLUMNS = [
        'dotations_amortissements' => [
            1 => 'Type', 2 => "Date d'entrée", 3 => "Valeur à amortir — Prix d'acquisition",
            4 => 'Valeur à amortir — Valeur comptable après réévaluation',
            5 => 'Amortissements antérieurs', 6 => 'Taux', 7 => 'Durée',
            8 => "Amortissements normaux ou accélérés de l'exercice",
            9 => "Total des amortissements à la fin de l'exercice", 10 => 'Observations',
        ],
        'locations_baux' => [
            1 => 'Nature du bien loué', 3 => 'Nom et prénoms du propriétaire',
            5 => 'Adresse du propriétaire', 9 => "Date de conclusion de l'acte de location",
            10 => 'Montant annuel de location',
        ],
    ];

    private const FIELDS = [
        'dotations_amortissements' => ['montant_global' => 'Montant global'],
        'passage_fiscal' => [
            'reintegration_courante_0_label' => 'Réintégration courante — Libellé — Ligne 1',
            'reintegration_courante_0_montant' => 'Réintégration courante — Montant — Ligne 1',
            'reintegration_non_courante_0_label' => 'Réintégration non courante — Libellé — Ligne 1',
            'reintegration_non_courante_0_montant' => 'Réintégration non courante — Montant — Ligne 1',
            'reintegrations_courantes_total' => 'Total des réintégrations courantes',
            'reintegrations_non_courantes_total' => 'Total des réintégrations non courantes',
            'deductions_courantes_total' => 'Total des déductions courantes',
            'deductions_non_courantes_total' => 'Total des déductions non courantes',
            'reports_deficitaires_total' => 'Total des reports déficitaires',
        ],
        'affectation_resultats' => [
            'decision_date' => "Date de décision de l'assemblée générale",
            'ligne1_montantB' => 'Réserve légale',
            'ligne4_montantA' => "Résultat net de l'exercice — Origine des résultats à affecter",
            'ligne4_montantB' => 'Dividendes',
            'ligne6_montantB' => 'Report à nouveau — Affectation des résultats',
            'total_A' => 'Total A — Origine des résultats à affecter',
            'total_B' => 'Total B — Affectation des résultats',
        ],
    ];

    private const METHODS = [
        '0' => ['Actif immobilisé', [
            1 => 'Immobilisations en non-valeurs', 2 => 'Immobilisations incorporelles',
            3 => 'Immobilisations corporelles', 4 => 'Immobilisations financières',
            6 => "Méthodes d'amortissements", 7 => "Méthodes d'évaluation des provisions pour dépréciation",
            8 => 'Méthodes de détermination des écarts de conversion — Actif',
        ]],
        '1' => ['Actif circulant hors trésorerie', [
            1 => 'Stocks', 2 => 'Créances', 3 => 'Titres et valeurs de placement',
            5 => "Méthodes d'évaluation des provisions pour dépréciation",
            6 => 'Méthodes de détermination des écarts de conversion — Actif',
        ]],
        '2' => ['Financement permanent', [
            0 => 'Méthodes de réévaluation', 1 => "Méthodes d'évaluation des provisions réglementées",
            2 => 'Dettes de financement permanent',
            3 => "Méthodes d'évaluation des provisions durables pour risques et charges",
            4 => 'Méthodes de détermination des écarts de conversion — Passif',
        ]],
        '3' => ['Passif circulant hors trésorerie', [
            0 => 'Dettes du passif circulant',
            1 => "Méthodes d'évaluation des autres provisions pour risques et charges",
            2 => 'Méthodes de détermination des écarts de conversion — Passif',
        ]],
        '4' => ['Trésorerie', [
            0 => 'Trésorerie — Actif', 1 => 'Trésorerie — Passif',
            2 => "Méthodes d'évaluation des provisions pour dépréciation",
        ]],
    ];

    public static function fieldLabel(string $tableauCode, string $key): string
    {
        if (isset(self::FIELDS[$tableauCode][$key])) {
            return self::FIELDS[$tableauCode][$key];
        }
        if (preg_match('/^r(\d+)_c(\d+)$/', $key, $m) === 1
            && isset(self::ROW_COLUMNS[$tableauCode][$m[2]])) {
            return self::ROW_COLUMNS[$tableauCode][$m[2]].' — Ligne '.((int) $m[1] + 1);
        }
        if ($tableauCode === 'dotations_amortissements'
            && preg_match('/^total_c(3|4|5|8|9)$/', $key, $m) === 1) {
            return 'Total — '.self::ROW_COLUMNS[$tableauCode][$m[1]];
        }
        if ($tableauCode === 'methodes_evaluation'
            && preg_match('/^methode_(\d+)_(\d+)$/', $key, $m) === 1
            && isset(self::METHODS[$m[1]][1][$m[2]])) {
            return self::METHODS[$m[1]][0].' — '.self::METHODS[$m[1]][1][$m[2]].' — Méthodes / Justifications';
        }
        if ($tableauCode === 'derogations'
            && preg_match('/^derogation_([0-2])_(justification|influence)$/', $key, $m) === 1) {
            $sections = ['Principes comptables fondamentaux', "Méthodes d'évaluation", "Règles d'établissement et de présentation des états de synthèse"];
            return 'Dérogations — '.$sections[$m[1]].' — '.self::detailLabel($m[2]);
        }
        if ($tableauCode === 'changements_methodes'
            && preg_match('/^changement_([01])_([0-2])_(nature|justification|influence)$/', $key, $m) === 1) {
            $sections = ["Méthodes d'évaluation", 'Règles de présentation'];
            return 'Changements — '.$sections[$m[1]].' — '.self::detailLabel($m[3]).' — Ligne '.((int) $m[2] + 1);
        }
        if ($tableauCode !== 'repartition_capital') {
            return $key;
        }

        if ($key === 'montant_capital') {
            return 'Montant du capital social';
        }

        if (preg_match('/^r(\d+)_c(\d+)$/', $key, $matches) === 1
            && isset(self::CAPITAL_COLUMNS[$matches[2]])) {
            return self::CAPITAL_COLUMNS[$matches[2]].' — Associé '.((int) $matches[1] + 1);
        }

        if (preg_match('/^total_c(7|8|10|11|12)$/', $key, $matches) === 1) {
            return 'Total — '.self::CAPITAL_COLUMNS[$matches[1]];
        }

        return $key;
    }

    public static function tableLabel(string $tableauCode): string
    {
        return self::TABLES[$tableauCode] ?? $tableauCode;
    }

    private static function detailLabel(string $key): string
    {
        return match ($key) {
            'nature' => 'Nature',
            'justification' => 'Justification',
            'influence' => 'Influence sur le patrimoine, la situation financière et les résultats',
        };
    }
}
