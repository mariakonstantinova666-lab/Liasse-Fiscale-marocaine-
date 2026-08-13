<?php

return [
    /*
    |--------------------------------------------------------------------------
    | DGI SIMPL-IS EDI identifiers
    |--------------------------------------------------------------------------
    |
    | The table ids and cell codes below come from the local reference workbook
    | docs/D3Simpl2_N_N-1.xlsm (hidden sheet "Complements" and SIMPL_* names).
    | They are isolated here so a future official XSD/catalog update can be
    | applied without changing the XML orchestration service.
    |
    */
    'modele_id' => 7,

    'table_ids' => [
        'bilan_passif' => 1,
        'bilan_actif' => 2,
        'cpc' => 6,
        'passage_fiscal' => 7,
        'esg' => 32,
        'detail_cpc' => 34,
        'immobilisations' => 11,
        'credit_bail' => 23,
        'amortissements' => 24,
        'provisions' => 37,
        'plus_values' => 38,
        'titres_participation' => 39,
        'tva' => 40,
        'repartition_capital' => 41,
        'affectation_resultats' => 5,
        'dotations_amortissements' => 12,
        'plus_values_fusion' => 26,
        'interets_emprunts' => 27,
        'locations_baux' => 28,
        'detail_stocks' => 36,
        'operations_devises' => 200,
        'tableau_financement' => 203,
        'methodes_evaluation' => 220,
        'derogations' => 202,
        'changements_methodes' => 201,
        'calcul_is_encouragees' => 240,
    ],

    'required_complete_tables' => [
        'bilan_passif' => 'Bilan Passif',
        'bilan_actif' => 'Bilan Actif',
        'cpc' => 'Compte de Produits et Charges',
        'passage_fiscal' => 'Passage fiscal',
        'esg' => 'Etat des soldes de gestion',
        'detail_cpc' => 'Detail des postes CPC',
        'repartition_capital' => 'Repartition du capital social',
        'affectation_resultats' => 'Affectation des resultats',
        'dotations_amortissements' => 'Dotations aux amortissements',
        'immobilisations' => 'Tableau des immobilisations',
        'amortissements' => 'Tableau des amortissements',
        'provisions' => 'Tableau des provisions',
        'plus_values' => 'Plus ou moins-values',
        'titres_participation' => 'Titres de participation',
        'tva' => 'Detail de la TVA',
        'detail_stocks' => 'Etat detaille des stocks',
        'tableau_financement' => 'Tableau de financement',
        'locations_baux' => 'Locations et baux',
        'methodes_evaluation' => 'Methodes d evaluation',
        'derogations' => 'Derogations',
        'changements_methodes' => 'Changements de methodes',
    ],

    'direct_cells' => [
        'passage_fiscal' => [
            'benefice_net' => 817,
            'perte_nette' => 820,
            'reintegrations_total' => 18009,
            'deductions_total' => 18012,
            'total_montant_plus' => 833,
            'total_montant_moins' => 834,
            'benefice_brut_fiscal' => 839,
            'deficit_brut_fiscal' => 840,
            'benefice_net_fiscal' => 6874,
            'deficit_net_fiscal' => 6875,
            'cumul_amortissements_differes' => 854,
            'deficits_restants_n4' => 864,
            'deficits_restants_n3' => 865,
            'deficits_restants_n2' => 866,
            'deficits_restants_n1' => 867,
        ],

        'repartition_capital' => [
            'montant_capital' => 18,
            'total_c7' => 2096,
            'total_c8' => 2097,
            'total_c10' => 2099,
            'total_c11' => 2100,
            'total_c12' => 2101,
        ],

        'affectation_resultats' => [
            'ligne2_montantA' => 471,
            'ligne3_montantA' => 473,
            'ligne4_montantA' => 475,
            'ligne5_montantA' => 477,
            'ligne6_montantA' => 479,
            'total_A' => 481,
            'ligne1_montantB' => 483,
            'ligne2_montantB' => 485,
            'ligne3_montantB' => 487,
            'ligne4_montantB' => 489,
            'ligne5_montantB' => 491,
            'ligne6_montantB' => 493,
            'total_B' => 495,
        ],

        'dotations_amortissements' => [
            'total_c3' => 1088,
            'total_c4' => 1089,
            'total_c5' => 1090,
            'montant_global' => 1093,
            'total_c8' => 1093,
            'total_c9' => 1094,
        ],

        'plus_values' => [
            'total_c2' => 2037,
            'total_c3' => 2038,
            'total_c4' => 2039,
            'total_c5' => 2040,
            'total_c6' => 2041,
            'total_c7' => 2042,
            'total_c8' => 2043,
        ],

        'titres_participation' => [
            'total_c2' => 14066,
            'total_c3' => 2055,
            'total_c4' => 2056,
            'total_c5' => 2057,
            'total_c6' => 2058,
            'total_c7' => 2059,
            'total_c8' => 2060,
            'total_c9' => 2061,
            'total_c10' => 2062,
            'total_c11' => 2063,
        ],

        'methodes_evaluation' => [
            'methode_0_1' => 14342,
            'methode_0_2' => 14343,
            'methode_0_3' => 14344,
            'methode_0_4' => 14345,
            'methode_0_6' => 14301,
            'methode_0_7' => 14303,
            'methode_0_8' => 14305,
            'methode_1_1' => 14307,
            'methode_1_2' => 14309,
            'methode_1_3' => 14311,
            'methode_1_5' => 14313,
            'methode_1_6' => 14315,
            'methode_2_0' => 14317,
            'methode_2_1' => 14319,
            'methode_2_2' => 14321,
            'methode_2_3' => 14323,
            'methode_2_4' => 14325,
            'methode_3_0' => 14327,
            'methode_3_1' => 14329,
            'methode_3_2' => 14331,
            'methode_4_0' => 14333,
            'methode_4_1' => 14335,
            'methode_4_2' => 14337,
        ],

        'derogations' => [
            'derogation_0_justification' => 14098,
            'derogation_0_influence' => 14099,
            'derogation_1_justification' => 14101,
            'derogation_1_influence' => 14102,
            'derogation_2_justification' => 14104,
            'derogation_2_influence' => 14105,
        ],

        'changements_methodes' => [
            'changement_0_0_nature' => 14088,
            'changement_0_0_justification' => 14089,
            'changement_0_0_influence' => 14090,
            'changement_1_0_nature' => 14091,
            'changement_1_0_justification' => 14092,
            'changement_1_0_influence' => 14093,
        ],
    ],

    'non_edi_fields' => [
        'affectation_resultats.decision_date',
        'passage_fiscal.reports_deficitaires_total',
    ],

    'dynamic_rows' => [
        'passage_fiscal' => [
            'reintegration_courante' => [
                'label' => 821,
                'montant' => 822,
            ],
            'reintegration_non_courante' => [
                'label' => 824,
                'montant' => 825,
            ],
            'deduction_courante' => [
                'label' => 827,
                'montant' => 828,
            ],
            'deduction_non_courante' => [
                'label' => 829,
                'montant' => 830,
            ],
        ],

        'dotations_amortissements' => [
            'r' => [
                'c1' => 10061,
                'c2' => 1078,
                'c3' => 1079,
                'c4' => 1080,
                'c5' => 1081,
                'c6' => 1082,
                'c7' => 1083,
                'c8' => 1084,
                'c9' => 1085,
                'c10' => 1086,
            ],
        ],

        'repartition_capital' => [
            'r' => [
                'c1' => 2094,
                'c2' => 17887,
                'c3' => 13536,
                'c4' => 13537,
                'c5' => 14560,
                'c6' => 2095,
                'c7' => 2096,
                'c8' => 2097,
                'c9' => 2098,
                'c10' => 2099,
                'c11' => 2100,
                'c12' => 2101,
            ],
        ],

        'plus_values' => [
            'r' => [
                'c1' => 1929,
                'c2' => 1930,
                'c3' => 1931,
                'c4' => 1932,
                'c5' => 1933,
                'c6' => 1934,
                'c7' => 1935,
                'c8' => 1936,
            ],
        ],

        'titres_participation' => [
            'r' => [
                'c1' => 2044,
                'c2' => 14065,
                'c3' => 2045,
                'c4' => 2046,
                'c5' => 2047,
                'c6' => 2048,
                'c7' => 2049,
                'c8' => 2050,
                'c9' => 2051,
                'c10' => 2052,
                'c11' => 2053,
            ],
        ],

        'locations_baux' => [
            'r' => [
                'c1' => 1267,
                'c2' => 1268,
                'c3' => 1269,
                'c4' => 14964,
                'c5' => 14965,
                'c6' => 14040,
                'c7' => 14041,
                'c8' => 17919,
                'c9' => 1270,
                'c10' => 1271,
                'c11' => 1272,
                'c12' => 1273,
                'c13' => 1274,
            ],
        ],
    ],
];
