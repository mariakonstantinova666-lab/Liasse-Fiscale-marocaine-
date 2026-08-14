<?php

namespace App\Services;

use App\Models\BalanceItem;
use Illuminate\Support\Collection;

/**
 * Point unique de calcul des tableaux de liasse non persistes.
 *
 * Les controleurs et les exports EDI doivent passer par ce service afin que
 * les montants affiches et exportes proviennent exactement des memes calculs.
 */
class LiasseTableDataService
{
    public function __construct(private BalanceService $balanceService)
    {
    }

    /**
     * @return array{data:array<string, array<string, object>>, totaux:array<string, object>, exercice:int}
     */
    public function bilanActif(int $userId, int $exercice): array
    {
        [$items, $itemsPrev] = $this->balanceService->lignesAvecPrecedent($userId, $exercice);

        $data = [
            'IMMOBILISATION EN NON VALEUR ( a )' => [
                'Frais préliminaires' => $this->calculerLigneActif($items, '211', '2811', $itemsPrev),
                'Charges à répartir sur plusieurs exercices' => $this->calculerLigneActif($items, '212', '2812', $itemsPrev),
                'Primes de remboursement des obligations' => $this->calculerLigneActif($items, '213', '2813', $itemsPrev),
            ],
            'IMMOBILISATIONS INCORPORELLES ( b )' => [
                'Immobilisations en recherche et développement' => $this->calculerLigneActif($items, '221', '2821', $itemsPrev),
                'Brevets, marques, droits et valeurs similaires' => $this->calculerLigneActif($items, '222', '2822', $itemsPrev),
                'Fonds commercial' => $this->calculerLigneActif($items, '223', '2823', $itemsPrev),
                'Autres immobilisations incorporelles' => $this->calculerLigneActif($items, '228', '2828', $itemsPrev),
            ],
            'IMMOBILISATIONS CORPORELLES ( c )' => [
                'Terrains' => $this->calculerLigneActif($items, '231', '2831', $itemsPrev),
                'Constructions' => $this->calculerLigneActif($items, '232', '2832', $itemsPrev),
                'Installations techniques, matériel et outillage' => $this->calculerLigneActif($items, '233', '2833', $itemsPrev),
                'Matériel de transport' => $this->calculerLigneActif($items, '234', '2834', $itemsPrev),
                'Mobiliers, matériel de bureau et aménagements divers' => $this->calculerLigneActif($items, '235', '2835', $itemsPrev),
                'Autres immobilisations corporelles' => $this->calculerLigneActif($items, '238', '2838', $itemsPrev),
                'Immobilisations corporelles en cours' => $this->calculerLigneActif($items, '239', '2839', $itemsPrev),
            ],
            'IMMOBILISATIONS FINANCIERES ( d )' => [
                'Prêts immobilisés' => $this->calculerLigneActif($items, '241', '2941', $itemsPrev),
                'Autres créances financières' => $this->calculerLigneActif($items, '248', '2948', $itemsPrev),
                'Titres de participation' => $this->calculerLigneActif($items, '251', '2951', $itemsPrev),
                'Autres titres immobilisés' => $this->calculerLigneActif($items, '258', '2958', $itemsPrev),
            ],
            'ECARTS DE CONVERSION - ACTIF ( e )' => [
                'Diminution des cadres immobilisées' => $this->calculerLigneActif($items, '271', null, $itemsPrev),
                'Augmentation des dettes de financement' => $this->calculerLigneActif($items, '272', null, $itemsPrev),
            ],
            'STOCKS ( f )' => [
                'Marchandises' => $this->calculerLigneActif($items, '311', '3911', $itemsPrev),
                'Matières et fournitures consommables' => $this->calculerLigneActif($items, '312', '3912', $itemsPrev),
                'Produits en cours' => $this->calculerLigneActif($items, '313', '3913', $itemsPrev),
                'Produits intermédiaires et produits résiduels' => $this->calculerLigneActif($items, '314', '3914', $itemsPrev),
                'Produits finis' => $this->calculerLigneActif($items, '315', '3915', $itemsPrev),
            ],
            'CREANCES DE L\'ACTIF CIRCULANT ( g )' => [
                'Fournisseurs débiteurs, avances et acomptes' => $this->calculerLigneActif($items, '341', '3941', $itemsPrev),
                'Clients et comptes rattachés' => $this->calculerLigneActif($items, '342', '3942', $itemsPrev),
                'Personnel' => $this->calculerLigneActif($items, '343', '3943', $itemsPrev),
                'Etat' => $this->calculerLigneActif($items, '345', '3945', $itemsPrev),
                'Comptes d\'associés' => $this->calculerLigneActif($items, '346', '3946', $itemsPrev),
                'Autres débiteurs' => $this->calculerLigneActif($items, '348', '3948', $itemsPrev),
                'Comptes d\'régularisation actif' => $this->calculerLigneActif($items, '349', '3949', $itemsPrev),
            ],
            'TITRES ET VALEURS DE PLACEMENT ( h )' => [
                'Titres et valeurs de placement' => $this->calculerLigneActif($items, '350', '3950', $itemsPrev),
            ],
            'ECART DE CONVERSION - ACTIF ( i ) (Elém. Circul.)' => [
                'Écarts de conversion - Actif (Éléments Circulants)' => $this->calculerLigneActif($items, '370', null, $itemsPrev),
            ],
            'TRESORERIE - ACTIF' => [
                'Chèques et valeurs à encaisser' => $this->calculerLigneActif($items, '511', null, $itemsPrev),
                'Banques, T.G & CP' => $this->calculerLigneActif($items, '514', null, $itemsPrev),
                'Caisses, régies d\'avances et accréditifs' => $this->calculerLigneActif($items, '516', null, $itemsPrev),
            ],
        ];

        $totaux = [
            'TOTAL_I' => $this->sommerRubriquesActif($data, [
                'IMMOBILISATION EN NON VALEUR ( a )',
                'IMMOBILISATIONS INCORPORELLES ( b )',
                'IMMOBILISATIONS CORPORELLES ( c )',
                'IMMOBILISATIONS FINANCIERES ( d )',
                'ECARTS DE CONVERSION - ACTIF ( e )',
            ]),
            'TOTAL_II' => $this->sommerRubriquesActif($data, [
                'STOCKS ( f )',
                'CREANCES DE L\'ACTIF CIRCULANT ( g )',
                'TITRES ET VALEURS DE PLACEMENT ( h )',
                'ECART DE CONVERSION - ACTIF ( i ) (Elém. Circul.)',
            ]),
            'TOTAL_III' => $this->sommerRubriquesActif($data, ['TRESORERIE - ACTIF']),
        ];

        return compact('data', 'totaux', 'exercice');
    }

    /**
     * @return array{data:array<string, array<string, object>>, totaux:array<string, object>, exercice:int}
     */
    public function bilanPassif(int $userId, int $exercice): array
    {
        [$items, $itemsPrev] = $this->balanceService->lignesAvecPrecedent($userId, $exercice);
        $ligne = fn ($codes) => $this->calculerLignePassif($items, $codes, $itemsPrev);

        $data = [
            'CAPITAUX PROPRES' => [
                'Capital social ou personnel (1)' => $ligne('1111'),
                'moins : Actionnaires, capital souscrit non appelé' => $ligne('1119'),
                'Prime d\'émission, de fusion, d\'apport' => $ligne('112'),
                'Écarts de réévaluation' => $ligne('113'),
                'Réserve légale' => $ligne('114'),
                'Autres réserves' => $ligne('115'),
                'Report à nouveau (2)' => $ligne(['116', '117']),
                'Résultat net en instance d\'affectation (2)' => $ligne('118'),
                'Résultat net de l\'exercice (2)' => (object) [
                    'montant' => $this->montant($items, '7', 'produit') - $this->montant($items, '6', 'charge'),
                    'montant_prec' => $this->montant($itemsPrev, '7', 'produit') - $this->montant($itemsPrev, '6', 'charge'),
                ],
            ],
            'CAPITAUX PROPRES ASSIMILES ( b )' => [
                'Subventions d\'investissement' => $ligne('131'),
                'Provisions réglementées' => $ligne('135'),
            ],
            'DETTES DE FINANCEMENT ( c )' => [
                'Emprunts obligataires' => $ligne('141'),
                'Autres dettes de financement' => $ligne('148'),
            ],
            'PROVISIONS DURABLES POUR RISQUES ET CHARGES ( d )' => [
                'Provisions pour risks' => $ligne('151'),
                'Provisions pour charges' => $ligne('155'),
            ],
            'ECARTS DE CONVERSION - PASSIF ( e )' => [
                'Augmentation des créances immobilisées' => $ligne('171'),
                'Diminution des dettes de financement' => $ligne('172'),
            ],
            'DETTES DU PASSIF CIRCULANT ( f )' => [
                'Fournisseurs et comptes rattachés' => $ligne('441'),
                'Clients créditeurs, avances et acomptes' => $ligne('442'),
                'Personnel' => $ligne('443'),
                'Organismes sociaux' => $ligne('444'),
                'Etat' => $ligne('445'),
                'Comptes d\'associés' => $ligne('446'),
                'Autres créanciers' => $ligne('448'),
                'Comptes de regularisation - passif' => $ligne('449'),
            ],
            'AUTRES PROVISIONS POUR RISQUES ET CHARGES ( g )' => [
                'Autres provisions pour risques et charges' => $ligne('45'),
            ],
            'ECARTS DE CONVERSION - PASSIF ( h ) (Éléments Circulants)' => [
                'Écarts de conversion - Passif (Éléments Circulants)' => $ligne('47'),
            ],
            'TRESORERIE PASSIF' => [
                'Crédits d\'escompte' => $ligne('552'),
                'Crédits de trésorerie' => $ligne('553'),
                'Banques ( soldes créditeurs )' => $ligne('554'),
            ],
        ];

        $totaux = [
            'TOTAL_CAPITAUX_PROPRES' => $this->sommerRubriquesPassif($data, ['CAPITAUX PROPRES']),
            'TOTAL_I' => $this->sommerRubriquesPassif($data, [
                'CAPITAUX PROPRES',
                'CAPITAUX PROPRES ASSIMILES ( b )',
                'DETTES DE FINANCEMENT ( c )',
                'PROVISIONS DURABLES POUR RISQUES ET CHARGES ( d )',
                'ECARTS DE CONVERSION - PASSIF ( e )',
            ]),
            'TOTAL_II' => $this->sommerRubriquesPassif($data, [
                'DETTES DU PASSIF CIRCULANT ( f )',
                'AUTRES PROVISIONS POUR RISQUES ET CHARGES ( g )',
                'ECARTS DE CONVERSION - PASSIF ( h ) (Éléments Circulants)',
            ]),
            'TOTAL_III' => $this->sommerRubriquesPassif($data, ['TRESORERIE PASSIF']),
        ];

        return compact('data', 'totaux', 'exercice');
    }

    /**
     * @return array{cpcData:array<string, array<string, object>>, cpcRows:array<int, array<string, mixed>>, exercice:int}
     */
    public function cpc(int $userId, int $exercice): array
    {
        $items = BalanceItem::where('user_id', $userId)->where('exercice', $exercice)->get();
        $itemsPrev = BalanceItem::where('user_id', $userId)->where('exercice', $exercice - 1)->get();

        $cpcData = [
            'I. PRODUITS D\'EXPLOITATION' => [
                'Ventes de marchandises' => $this->calculateRow($items, $itemsPrev, '711'),
                'Ventes de biens et services produits' => $this->calculateRow($items, $itemsPrev, '712'),
                'Variation de stock de produits' => $this->calculateRow($items, $itemsPrev, '713'),
                'Immobilisations produites par l\'Ese p/elle même' => $this->calculateRow($items, $itemsPrev, '714'),
                'Subventions d\'exploitation' => $this->calculateRow($items, $itemsPrev, '716'),
                'Autres produits d\'exploitation' => $this->calculateRow($items, $itemsPrev, '718'),
                'Reprises d\'exploitation; transfert de charges' => $this->calculateRow($items, $itemsPrev, '719'),
            ],
            'II. CHARGES D\'EXPLOITATION' => [
                'Achats revendus de marchandises' => $this->calculateRow($items, $itemsPrev, '611'),
                'Achats consommés de matières et fournitures' => $this->calculateRow($items, $itemsPrev, '612'),
                'Autres charges externes' => $this->calculateRow($items, $itemsPrev, ['613', '614']),
                'Impôts et taxes' => $this->calculateRow($items, $itemsPrev, '616'),
                'Charges de personnel' => $this->calculateRow($items, $itemsPrev, '617'),
                'Autres charges d\'exploitation' => $this->calculateRow($items, $itemsPrev, '618'),
                'Dotations d\'exploitation' => $this->calculateRow($items, $itemsPrev, '619'),
            ],
            'IV. PRODUITS FINANCIERS' => [
                'Produits des titres de participation' => $this->calculateRow($items, $itemsPrev, '732'),
                'Gains de change' => $this->calculateRow($items, $itemsPrev, '733'),
                'Intérêts et autres produits financiers' => $this->calculateRow($items, $itemsPrev, '738'),
                'Reprises financières; transferts de charges' => $this->calculateRow($items, $itemsPrev, '739'),
            ],
            'V. CHARGES FINANCIERES' => [
                'Charges d\'intérêts' => $this->calculateRow($items, $itemsPrev, '631'),
                'Pertes de change' => $this->calculateRow($items, $itemsPrev, '633'),
                'Autres charges financières' => $this->calculateRow($items, $itemsPrev, '638'),
                'Dotations financières' => $this->calculateRow($items, $itemsPrev, '639'),
            ],
            'VIII. PRODUITS NON COURANTS' => [
                'Produits des cessions d\'immobilisations' => $this->calculateRow($items, $itemsPrev, '751'),
                'Subventions d\'équilibre' => $this->calculateRow($items, $itemsPrev, '756'),
                'Reprises sur subventions d\'investissement' => $this->calculateRow($items, $itemsPrev, '757'),
                'Autres produits non courants' => $this->calculateRow($items, $itemsPrev, '758'),
                'Reprises non courantes; transferts de charges' => $this->calculateRow($items, $itemsPrev, '759'),
            ],
            'IX. CHARGES NON COURANTES' => [
                'Valeurs nettes d\'amortis. des immos cédées' => $this->calculateRow($items, $itemsPrev, '651'),
                'Subventions accordées' => $this->calculateRow($items, $itemsPrev, '656'),
                'Autres charges non courantes' => $this->calculateRow($items, $itemsPrev, '658'),
                'Dotations non courantes aux amortiss. et prov.' => $this->calculateRow($items, $itemsPrev, '659'),
            ],
            'XII. IMPOTS SUR LES RÉSULTATS' => [
                'Impôts sur les résultats' => $this->calculateRow($items, $itemsPrev, '67'),
            ],
        ];

        return [
            'cpcData' => $cpcData,
            'cpcRows' => $this->cpcRows($cpcData),
            'exercice' => $exercice,
        ];
    }

    /**
     * @return array{immoData:array<string, array<string, object>>, totauxImmo:array<string, object>, exercice:int}
     */
    public function immobilisations(int $userId, int $exercice): array
    {
        [$items, $itemsPrev] = $this->balanceService->lignesAvecPrecedent($userId, $exercice);

        $immoData = [
            'IMMOBILISATIONS EN NON-VALEURS' => [
                'Frais préliminaires' => $this->calculerLigneImmo($items, '211', $itemsPrev),
                'Charges à répartir sur plusieurs exercices' => $this->calculerLigneImmo($items, '212', $itemsPrev),
                'Primes de remboursement des obligations' => $this->calculerLigneImmo($items, '213', $itemsPrev),
            ],
            'IMMOBILISATIONS INCORPORELLES' => [
                'Immobilisations en recherche et développement' => $this->calculerLigneImmo($items, '221', $itemsPrev),
                'Brevets, marques, droits et valeurs similaires' => $this->calculerLigneImmo($items, '222', $itemsPrev),
                'Fonds commercial' => $this->calculerLigneImmo($items, '223', $itemsPrev),
                'Autres immobilisations incorporelles' => $this->calculerLigneImmo($items, '228', $itemsPrev),
            ],
            'IMMOBILISATIONS CORPORELLES' => [
                'Terrains' => $this->calculerLigneImmo($items, '231', $itemsPrev),
                'Constructions' => $this->calculerLigneImmo($items, '232', $itemsPrev),
                'Installations techniques, matériel et outillage' => $this->calculerLigneImmo($items, '233', $itemsPrev),
                'Matériel de transport' => $this->calculerLigneImmo($items, '234', $itemsPrev),
                'Mobilier, matériel de bureau et aménagement' => $this->calculerLigneImmo($items, '235', $itemsPrev, ['2355']),
                'Autres immobilisations corporelles' => $this->calculerLigneImmo($items, '238', $itemsPrev),
                'Immobilisations corporelles en cours' => $this->calculerLigneImmo($items, '239', $itemsPrev),
                'Matériel informatique' => $this->calculerLigneImmo($items, '2355', $itemsPrev),
            ],
        ];

        $totauxImmo = [];
        foreach ($immoData as $rubrique => $lignes) {
            $totauxImmo[$rubrique] = $this->sumObjectRows($lignes, [
                'debut', 'acquisition', 'production', 'virement_aug',
                'cession', 'retrait', 'virement_dim', 'fin',
            ]);
        }

        return compact('immoData', 'totauxImmo', 'exercice');
    }

    /**
     * @return array{amortData:array<string, array<string, object>>, totauxAmort:array<string, object>, totalGeneral:object, exercice:int}
     */
    public function amortissements(int $userId, int $exercice): array
    {
        [$items, $itemsPrev] = $this->balanceService->lignesAvecPrecedent($userId, $exercice);

        $amortData = [
            'IMMOBILISATION EN NON-VALEURS' => [
                '- Frais préliminaires' => $this->calculerLigneAmortissement($items, $itemsPrev, '2811', '61911'),
                '- Charges à répartir sur plusieurs exercices' => $this->calculerLigneAmortissement($items, $itemsPrev, '2812', '61912'),
                '- Primes de remboursement obligations' => $this->calculerLigneAmortissement($items, $itemsPrev, '2813', '61913'),
            ],
            'IMMOBILISATIONS INCORPORELLES' => [
                '- Immobilisation en recherche et développement' => $this->calculerLigneAmortissement($items, $itemsPrev, '2821', '61921'),
                '- Brevets, marques, droits et valeurs similaires' => $this->calculerLigneAmortissement($items, $itemsPrev, '2822', '61922'),
                '- Fonds commercial' => $this->calculerLigneAmortissement($items, $itemsPrev, '2823', '61923'),
                '- Autres immobilisations incorporelles' => $this->calculerLigneAmortissement($items, $itemsPrev, '2828', '61928'),
            ],
            'IMMOBILISATIONS CORPORELLES' => [
                '- Terrains' => $this->calculerLigneAmortissement($items, $itemsPrev, '2831', '61931'),
                '- Constructions' => $this->calculerLigneAmortissement($items, $itemsPrev, '2832', '61932'),
                '- Installations techniques, matériel et outillage' => $this->calculerLigneAmortissement($items, $itemsPrev, '2833', '61933'),
                '- Matériel de transport' => $this->calculerLigneAmortissement($items, $itemsPrev, '2834', '61934'),
                '- Mobilier, matériel de bureau et aménagement' => $this->calculerLigneAmortissement($items, $itemsPrev, '2835', '61935'),
                '- Autres immobilisations corporelles' => $this->calculerLigneAmortissement($items, $itemsPrev, '2838', '61938'),
                '- Immobilisations corporelles en cours' => $this->calculerLigneAmortissement($items, $itemsPrev, '2839', '61939'),
            ],
        ];

        $totauxAmort = [];
        foreach ($amortData as $rubrique => $lignes) {
            $totauxAmort[$rubrique] = $this->sumObjectRows($lignes, ['col1', 'col2', 'col3', 'col4']);
        }

        $totalGeneral = $this->sumObjectRows($totauxAmort, ['col1', 'col2', 'col3']);
        $totalGeneral->col4 = $totalGeneral->col1 + $totalGeneral->col2 - $totalGeneral->col3;

        return compact('amortData', 'totauxAmort', 'totalGeneral', 'exercice');
    }

    /**
     * @return array{provisionsData:array<string, array<string, object>>, totauxProvisions:array<string, object>, totalGeneral:object, exercice:int}
     */
    public function provisions(int $userId, int $exercice): array
    {
        $ligne = fn () => (object) ['col1' => 0.0, 'col2' => 0.0, 'col3' => 0.0, 'col4' => 0.0, 'col5' => 0.0, 'col6' => 0.0, 'col7' => 0.0];
        $provisionsData = [
            'PROVISIONS DURABLES POUR RISQUES ET CHARGES' => [
                '- Provisions pour litiges' => $ligne(),
                '- Provisions pour garanties données aux clients' => $ligne(),
                '- Provisions pour propres assureurs' => $ligne(),
                '- Provisions pour pertes sur marchés à terme' => $ligne(),
                '- Provisions pour amendes, doubles droits, pénalités' => $ligne(),
                '- Provisions pour charges à répartir sur plusieurs exercices' => $ligne(),
                '- Provisions pour retraites et obligations similaires' => $ligne(),
                '- Autres provisions durables pour risques et charges' => $ligne(),
            ],
            'AUTRES PROVISIONS POUR RISQUES ET CHARGES' => [
                '- Provisions pour litiges' => $ligne(),
                '- Provisions pour garanties données aux clients' => $ligne(),
                '- Provisions pour pertes sur marchés à terme' => $ligne(),
                '- Autres provisions pour risques et charges' => $ligne(),
            ],
            'PROVISIONS POUR DEPRECIATION DE L\'ACTIF' => [
                '- Provisions pour dépréciation de l\'immobilisation en non-valeurs' => $ligne(),
                '- Provisions pour dépréciation des immobilisations incorporelles' => $ligne(),
                '- Provisions pour dépréciation des immobilisations corporelles' => $ligne(),
                '- Provisions pour dépréciation des immobilisations financières' => $ligne(),
                '- Provisions pour dépréciation des stocks' => $ligne(),
                '- Provisions pour dépréciation des comptes clients' => $ligne(),
                '- Provisions pour dépréciation des autres comptes débiteurs' => $ligne(),
                '- Provisions pour dépréciation des titres et valeurs de placement' => $ligne(),
                '- Provisions pour dépréciation des comptes de trésorerie' => $ligne(),
            ],
        ];

        $totauxProvisions = [];
        foreach ($provisionsData as $rubrique => $lignes) {
            $totauxProvisions[$rubrique] = $this->sumObjectRows($lignes, ['col1', 'col2', 'col3', 'col4', 'col5', 'col6', 'col7']);
        }

        $totalGeneral = $this->sumObjectRows($totauxProvisions, ['col1', 'col2', 'col3', 'col4', 'col5', 'col6', 'col7']);

        return compact('provisionsData', 'totauxProvisions', 'totalGeneral', 'exercice');
    }

    /**
     * @return array{tvaRows:array<int, array<string, mixed>>, exercice:int}
     */
    public function tva(int $userId, int $exercice): array
    {
        [$items, $itemsPrev] = $this->balanceService->lignesAvecPrecedent($userId, $exercice);

        $solde = fn (Collection $collection, string $prefix, string $sens): float => (float) $collection
            ->filter(fn ($item) => str_starts_with((string) $item->compte, $prefix))
            ->sum(fn ($item) => $sens === 'credit'
                ? (float) $item->solde_crediteur - (float) $item->solde_debiteur
                : (float) $item->solde_debiteur - (float) $item->solde_crediteur);

        $ligne = fn (float $debut, float $fin): object => (object) [
            'debut' => $debut,
            'operations' => $fin - $debut,
            'declarations' => 0.0,
            'fin' => $fin,
        ];

        $facturee = $ligne($solde($itemsPrev, '4455', 'credit'), $solde($items, '4455', 'credit'));
        $recupImmo = $ligne($solde($itemsPrev, '34551', 'debit'), $solde($items, '34551', 'debit'));
        $recupTotal = $ligne($solde($itemsPrev, '3455', 'debit'), $solde($items, '3455', 'debit'));
        $recupCharges = $ligne($recupTotal->debut - $recupImmo->debut, $recupTotal->fin - $recupImmo->fin);
        $due = $ligne($facturee->debut - $recupTotal->debut, $facturee->fin - $recupTotal->fin);

        $tvaRows = [
            ['label' => 'A. T.V.A. Facturée', 'values' => $facturee, 'bold' => true],
            ['label' => 'B. T.V.A. Récupérable', 'values' => $recupTotal, 'bold' => true],
            ['label' => '- sur charges', 'values' => $recupCharges],
            ['label' => '- sur immobilisations', 'values' => $recupImmo],
            ['label' => 'C. T.V.A. due ou crédit de T.V.A = (A - B)', 'values' => $due, 'bold' => true],
        ];

        return compact('tvaRows', 'exercice');
    }

    /**
     * @return array{rows:array<int, array<string, mixed>>, n:array<string, float>, p:array<string, float>, exercice:int}
     */
    public function esg(int $userId, int $exercice): array
    {
        [$items, $itemsPrev] = $this->balanceService->lignesAvecPrecedent($userId, $exercice);
        $n = $this->calculerESG($items);
        $p = $this->calculerESG($itemsPrev);
        $sp = "\u{00a0}\u{00a0}\u{00a0}";

        $rows = [
            ['section' => 'I - TABLEAU DE FORMATION DU RESULTAT ( T.F.R )'],
            ['l' => "1{$sp}Ventes de marchandises (en l'état )", 'k' => 'ventesMarch'],
            ['l' => "2{$sp}-  Achats revendus de marchandises", 'k' => 'achatsRevendus'],
            ['l' => "I{$sp}MARGES BRUTES SUR VENTES EN L'ETAT", 'k' => 'margeBrute', 'bold' => true],
            ['l' => "II{$sp}+  PRODUCTION DE L'EXERCICE (3+4+5)", 'k' => 'production', 'bold' => true],
            ['l' => "3{$sp}Ventes de biens et services produits", 'k' => 'ventesBiens'],
            ['l' => "4{$sp}Variation de stocks de produits", 'k' => 'varStock'],
            ['l' => "5{$sp}Immobilisations produites par l'entreprise pour elle même", 'k' => 'immobProduites'],
            ['l' => "III{$sp}-  CONSOMMATION DE L'EXERCICE (6+7)", 'k' => 'consommation', 'bold' => true],
            ['l' => "6{$sp}Achats consommés de matières et fournitures", 'k' => 'achatsConsommes'],
            ['l' => "7{$sp}Autres charges externes", 'k' => 'autresChargesExt'],
            ['l' => "IV{$sp}VALEUR AJOUTEE ( I+II+III )", 'k' => 'va', 'bold' => true],
            ['l' => "8{$sp}+  Subventions d'exploitation", 'k' => 'subvExpl'],
            ['l' => "V{$sp}RESULTAT BRUT D'EXPLOITATION (E.B.E)", 'k' => 'ebe', 'bold' => true],
            ['l' => "9{$sp}-  Impôts et taxes", 'k' => 'impotsTaxes'],
            ['l' => "10{$sp}-  Charges de personnel", 'k' => 'chargesPersonnel'],
            ['l' => "11{$sp}+  Autres produits d'exploitation", 'k' => 'autresProdExpl'],
            ['l' => "12{$sp}-  Autres charges d'exploitation", 'k' => 'autresChargesExpl'],
            ['l' => "13{$sp}+  Reprises d'exploitation: transfert de charges", 'k' => 'reprisesExpl'],
            ['l' => "14{$sp}-  Dotations d'exploitation", 'k' => 'dotationsExpl'],
            ['l' => "VI{$sp}RESULTAT D'EXPLOITATION ( + ou - )", 'k' => 'resExpl', 'bold' => true],
            ['l' => "VII{$sp}RESULTAT FINANCIER", 'k' => 'resFin', 'bold' => true],
            ['l' => "VIII{$sp}RESULTAT COURANT ( + ou - )", 'k' => 'resCourant', 'bold' => true],
            ['l' => "IX{$sp}RESULTAT NON COURANT ( + ou - )", 'k' => 'resNC', 'bold' => true],
            ['l' => "15{$sp}-  Impôts sur les resultats", 'k' => 'impotsResultats'],
            ['l' => "X{$sp}RESULTAT NET DE L'EXERCICE ( + ou - )", 'k' => 'resNet', 'bold' => true],
            ['section' => "II - CAPACITE D'AUTOFINANCEMENT ( C.A.F ) - AUTOFINANCEMENT"],
            ['l' => "1{$sp}RESULTAT NET DE L'EXERCICE ( + ou - )", 'k' => 'resNet'],
            ['l' => '- Benefice (+)', 'k' => 'benefice', 'indent' => true],
            ['l' => '- Perte   (-)', 'k' => 'perte', 'indent' => true],
            ['l' => "2{$sp}+  Dotations d'exploitation", 'k' => 'dotationsExpl'],
            ['l' => "3{$sp}+  Dotations financières", 'k' => 'dotFin'],
            ['l' => "4{$sp}+  Dotations non courantes", 'k' => 'dotNC'],
            ['l' => "5{$sp}-  Reprises d'exploitation", 'k' => 'reprisesExpl'],
            ['l' => "6{$sp}-  Reprises financières", 'k' => 'reprFin'],
            ['l' => "7{$sp}-  Reprises non courantes (2) (3)", 'k' => 'reprNC'],
            ['l' => "8{$sp}-  Produits des cession des immobilisations (1)", 'k' => 'produitsCession'],
            ['l' => "9{$sp}+  Valeurs nettes des immobilisations cédées", 'k' => 'vnaCedees'],
            ['l' => "I{$sp}CAPACITE D'AUTOFINANCEMENT ( C.A.F )", 'k' => 'caf', 'total' => true],
            ['l' => "10{$sp}-  Distributions de bénéfices", 'k' => 'distributions'],
            ['l' => "II{$sp}AUTOFINANCEMENT", 'k' => 'autofinancement', 'total' => true],
        ];

        return compact('rows', 'n', 'p', 'exercice');
    }

    /**
     * @return array{rows:array<int, array<string, mixed>>, exercice:int}
     */
    public function detailCpc(int $userId, int $exercice): array
    {
        [$items, $itemsPrev] = $this->balanceService->lignesAvecPrecedent($userId, $exercice);

        $def = [
            ['section' => "CHARGES D'EXPLOITATION"],
            ['poste' => '611', 'label' => 'Achats revendus de marchandises', 'type' => 'charge', 'details' => [
                ['Achats de marchandises', '6111'],
                ['Variation des stocks de marchandises (+/-)', '6114'],
            ]],
            ['poste' => '612', 'label' => 'Achats consommés de matières et fournitures', 'type' => 'charge', 'details' => [
                ['Achats de matières premières', '6121'],
                ['Variation des stocks de matières premières (+/-)', '6124'],
                ["Achats de matières et fournitures consommables et d'emballages", ['6122', '6123']],
                ['Variation des stocks de matières, fournitures et emballages (+/-)', '6125'],
                ['Achats non stockés de matières et de fournitures', '6126'],
                ['Achats de travaux, études et prestation de services', '6127'],
            ]],
            ['poste' => ['613', '614'], 'label' => 'Autres charges externes', 'type' => 'charge', 'details' => [
                ['Locations et charges locatives', '6131'],
                ['Redevances de crédit-bail', '6132'],
                ['Entretient et réparations', '6133'],
                ["Primes d'assurances", '6134'],
                ["Rémunérations du personnel extérieur à l'entreprise", '6135'],
                ["Rémunérations d'intermédiaires et honoraires", '6136'],
                ['Redevances pour brevets, marque, droits ...', '6137'],
                ['Transports', '6142'],
                ['Déplacements, missions et réceptions', '6143'],
                ['Reste du poste des autres charges externes', 'RESTE'],
            ]],
            ['poste' => '617', 'label' => 'Charges de personnel', 'type' => 'charge', 'details' => [
                ['Rémunération du personnel', '6171'],
                ['Charges sociales', '6174'],
                ['Reste du poste des charges de personnel', 'RESTE'],
            ]],
            ['poste' => '618', 'label' => "Autres charges d'exploitation", 'type' => 'charge', 'details' => [
                ['Jetons de présence', '6181'],
                ['Pertes sur créances irrécouvrables', '6182'],
                ["Reste du poste des autres charges d'exploitation", 'RESTE'],
            ]],
            ['section' => 'CHARGES FINANCIERES'],
            ['poste' => '638', 'label' => 'Autres charges financières', 'type' => 'charge', 'details' => [
                ['Charges nettes sur cessions de titres et valeurs de placement', '6385'],
                ['Reste du poste des autres charges financières', 'RESTE'],
            ]],
            ['section' => 'CHARGES NON COURANTES'],
            ['poste' => '658', 'label' => 'Autres charges non courantes', 'type' => 'charge', 'details' => [
                ['Pénalités sur marchés et débits', '6581'],
                ["Rappels d'impôts (autres qu'impôts sur les résultats)", '6582'],
                ['Pénalités et amendes fiscales et pénales', '6583'],
                ['Créances devenues irrécouvrables', '6585'],
                ['Reste du poste des autres charges non courantes', 'RESTE'],
            ]],
            ['section' => "PRODUITS D'EXPLOITATION"],
            ['poste' => '711', 'label' => 'Ventes de marchandises', 'type' => 'produit', 'details' => [
                ['Ventes de marchandises au Maroc', '7111'],
                ["Ventes de marchandises à l'étranger", '7113'],
                ['Reste du poste des ventes de marchandises', 'RESTE'],
            ]],
            ['poste' => '712', 'label' => 'Ventes des biens et services produits', 'type' => 'produit', 'details' => [
                ['Ventes de biens au Maroc', '7121'],
                ["Ventes de biens à l'étranger", '7122'],
                ['Ventes des services au Maroc', '7124'],
                ["Ventes des services à l'étranger", '7125'],
                ['Redevances pour brevets, marques, droits ...', '7126'],
                ['Reste du poste des ventes et services produits', 'RESTE'],
            ]],
            ['poste' => '713', 'label' => 'Variation des stocks de produits', 'type' => 'produit', 'details' => [
                ['Variation des stocks de produits de produits en cours', '7131'],
                ['Variation des stocks de biens produits', '7132'],
                ['Variation des stocks de services en cours', '7134'],
            ]],
            ['poste' => '718', 'label' => "Autres produits d'exploitation", 'type' => 'produit', 'details' => [
                ['Jetons de présence reçus', '7181'],
                ['Reste du poste (produits divers)', 'RESTE'],
            ]],
            ['poste' => '719', 'label' => "Reprises d'exploitation, transferts de charges", 'type' => 'produit', 'details' => [
                ['Reprises', 'RESTE'],
                ['Transferts de charges', '7197'],
            ]],
            ['section' => 'PRODUITS FINANCIERS'],
            ['poste' => '738', 'label' => 'Intérêts et autres produits financiers', 'type' => 'produit', 'details' => [
                ['Intérêt et produits assimilés', '7381'],
                ['Revenus des créances rattachées à des participations', '7383'],
                ['Produits nets sur cessions de titres et valeurs de placement', '7385'],
                ['Reste du poste intérêts et autres produits financiers', 'RESTE'],
            ]],
        ];

        $rows = [];
        foreach ($def as $bloc) {
            if (isset($bloc['section'])) {
                $rows[] = ['section' => $bloc['section']];
                continue;
            }

            $type = $bloc['type'];
            $totalN = $this->montant($items, $bloc['poste'], $type);
            $totalP = $this->montant($itemsPrev, $bloc['poste'], $type);
            $code = is_array($bloc['poste']) ? implode('/', $bloc['poste']) : $bloc['poste'];
            $rows[] = ['poste' => $code."\u{00a0}\u{00a0}".$bloc['label']];

            $sommeN = 0.0;
            $sommeP = 0.0;
            $resteIdx = null;
            foreach ($bloc['details'] as [$libelle, $codeDetail]) {
                if ($codeDetail === 'RESTE') {
                    $rows[] = ['l' => $libelle, 'n' => 0.0, 'p' => 0.0];
                    $resteIdx = array_key_last($rows);
                    continue;
                }

                $vN = $this->montant($items, $codeDetail, $type);
                $vP = $this->montant($itemsPrev, $codeDetail, $type);
                $sommeN += $vN;
                $sommeP += $vP;
                $rows[] = ['l' => $libelle, 'n' => $vN, 'p' => $vP];
            }

            if ($resteIdx !== null) {
                $rows[$resteIdx]['n'] = $totalN - $sommeN;
                $rows[$resteIdx]['p'] = $totalP - $sommeP;
            }

            $rows[] = ['total' => true, 'l' => 'Total', 'n' => $totalN, 'p' => $totalP];
        }

        return compact('rows', 'exercice');
    }

    /**
     * @return array{stockSections:array<string, array<int, array<string, mixed>>>, stockTotals:array<string, object>, stockTotalGeneral:object, exercice:int}
     */
    public function detailStocks(int $userId, int $exercice): array
    {
        [$items, $itemsPrev] = $this->balanceService->lignesAvecPrecedent($userId, $exercice);
        $definitions = [
            'I. Stocks Approvisionnement' => [
                ['group' => "Biens et produits destinés à la revente en l'état"],
                ['label' => 'Biens immeubles', 'brut' => [], 'provision' => []],
                ['label' => 'Biens meubles', 'brut' => ['311'], 'provision' => ['3911']],
                ['group' => 'Biens et matières premières destinés aux activités de production et de transformation'],
                ['label' => 'Matières premières', 'brut' => ['3121'], 'provision' => ['39121']],
                ['label' => 'Matières consommables', 'brut' => ['3122', '3126', '3128'], 'provision' => ['39122', '39126', '39128']],
                ['label' => 'Pièces détachées', 'brut' => [], 'provision' => []],
                ['group' => 'Emballages'],
                ['label' => 'Récupérables', 'brut' => ['31232'], 'provision' => ['391232']],
                ['label' => 'Vendus', 'brut' => [], 'provision' => []],
                ['label' => 'Perdus', 'brut' => ['31231'], 'provision' => ['391231']],
            ],
            'II. Stocks En-cours Production de Biens et Services' => [
                ['label' => 'Produits en cours', 'brut' => ['3131', '3138', '3141', '3148'], 'provision' => ['39131', '39138', '39141', '39148']],
                ['label' => 'Études en cours', 'brut' => ['31342'], 'provision' => ['391342']],
                ['label' => 'Travaux en cours', 'brut' => ['31341'], 'provision' => ['391341']],
                ['label' => 'Services en cours', 'brut' => ['31343'], 'provision' => ['391343']],
            ],
            'III. Stocks Produits finis' => [
                ['label' => 'Produits finis', 'brut' => ['315'], 'provision' => ['3915']],
                ['label' => 'Biens finis', 'brut' => [], 'provision' => []],
            ],
            'IV. Stocks Produits Résiduels' => [
                ['label' => 'Déchets', 'brut' => ['31451'], 'provision' => ['391451']],
                ['label' => 'Rebuts', 'brut' => ['31452'], 'provision' => ['391452']],
                ['label' => 'Matières de récupération', 'brut' => ['31453'], 'provision' => ['391453']],
            ],
        ];

        $stockSections = [];
        foreach ($definitions as $section => $lignes) {
            foreach ($lignes as $definition) {
                if (isset($definition['group'])) {
                    $stockSections[$section][] = ['group' => $definition['group']];
                    continue;
                }

                $stockSections[$section][] = [
                    'label' => $definition['label'],
                    'values' => $this->calculerLigneStock($items, $itemsPrev, $definition['brut'], $definition['provision']),
                ];
            }
        }

        $stockTotals = [];
        foreach ($stockSections as $section => $lignes) {
            $stockTotals[$section] = $this->totaliserLignesStock(array_map(
                fn ($ligne) => $ligne['values'] ?? null,
                $lignes
            ));
        }

        $stockTotalGeneral = $this->totaliserLignesStock($stockTotals);

        return compact('stockSections', 'stockTotals', 'stockTotalGeneral', 'exercice');
    }

    /**
     * @return array{synthese:array<int, array<string, mixed>>, fluxRows:array<int, array<string, mixed>>, fluxTotal:object, exercice:int}
     */
    public function tableauFinancement(int $userId, int $exercice): array
    {
        [$items, $itemsPrev] = $this->balanceService->lignesAvecPrecedent($userId, $exercice);
        $masses = function (Collection $collection): array {
            $passif = function (array $prefixes) use ($collection): float {
                return (float) $collection->filter(function ($item) use ($prefixes) {
                    foreach ($prefixes as $prefix) {
                        if (str_starts_with((string) $item->compte, $prefix)) {
                            return true;
                        }
                    }

                    return false;
                })->sum(fn ($item) => (float) $item->solde_crediteur - (float) $item->solde_debiteur);
            };

            $resultat = $this->montant($collection, '7', 'produit') - $this->montant($collection, '6', 'charge');
            $fp = $passif(['111', '112', '113', '114', '115', '116', '117', '118', '131', '135', '141', '148', '151', '155', '171', '172']) + $resultat;
            $immBrut = (float) $collection->filter(fn ($item) => str_starts_with((string) $item->compte, '2')
                && !str_starts_with((string) $item->compte, '28')
                && !str_starts_with((string) $item->compte, '29'))
                ->sum(fn ($item) => (float) $item->solde_debiteur - (float) $item->solde_crediteur);
            $immAmort = (float) $collection->filter(fn ($item) => str_starts_with((string) $item->compte, '28')
                || str_starts_with((string) $item->compte, '29'))
                ->sum(fn ($item) => (float) $item->solde_crediteur - (float) $item->solde_debiteur);
            $actifImmo = $immBrut - $immAmort;
            $fr = $fp - $actifImmo;
            $acBrut = (float) $collection->filter(fn ($item) => str_starts_with((string) $item->compte, '3')
                && !str_starts_with((string) $item->compte, '39'))
                ->sum(fn ($item) => (float) $item->solde_debiteur - (float) $item->solde_crediteur);
            $acProv = (float) $collection->filter(fn ($item) => str_starts_with((string) $item->compte, '39'))
                ->sum(fn ($item) => (float) $item->solde_crediteur - (float) $item->solde_debiteur);
            $ac = $acBrut - $acProv;
            $pc = $passif(['441', '442', '443', '444', '445', '446', '448', '449', '45', '47']);
            $bfg = $ac - $pc;
            $tn = $fr - $bfg;

            return compact('fp', 'actifImmo', 'fr', 'ac', 'pc', 'bfg', 'tn');
        };

        $n = $masses($items);
        $p = $masses($itemsPrev);
        $synthese = [
            ['l' => '1&nbsp;&nbsp;Financement Permanent', 'k' => 'fp', 'sensPos' => 'ressource'],
            ['l' => '2&nbsp;&nbsp;Moins actif immobilisé', 'k' => 'actifImmo', 'sensPos' => 'emploi'],
            ['l' => '3&nbsp;&nbsp;= Fonds de roulement fonctionnel (1-2) (A)', 'k' => 'fr', 'sensPos' => 'ressource', 'total' => true],
            ['l' => '4&nbsp;&nbsp;Actif circulant', 'k' => 'ac', 'sensPos' => 'emploi'],
            ['l' => '5&nbsp;&nbsp;Moins passif circulant', 'k' => 'pc', 'sensPos' => 'ressource'],
            ['l' => '6&nbsp;&nbsp;= Besoin de financement global (4-5) (B)', 'k' => 'bfg', 'sensPos' => 'emploi', 'total' => true],
            ['l' => '7&nbsp;&nbsp;TRESORERIE NETTE (Actif-Passif) = A-B', 'k' => 'tn', 'sensPos' => 'emploi', 'total' => true],
        ];

        foreach ($synthese as &$row) {
            $row['n'] = $n[$row['k']];
            $row['p'] = $p[$row['k']];
            $var = $row['n'] - $row['p'];
            $estRessource = ($row['sensPos'] === 'ressource') ? $var >= 0 : $var < 0;
            $row['emploi'] = $estRessource ? 0.0 : abs($var);
            $row['ressource'] = $estRessource ? abs($var) : 0.0;
        }
        unset($row);

        $variationBrute = function (array $prefixes) use ($items, $itemsPrev): float {
            $brut = fn (Collection $collection): float => (float) $collection
                ->filter(function ($item) use ($prefixes) {
                    foreach ($prefixes as $prefix) {
                        if (str_starts_with((string) $item->compte, $prefix)) {
                            return true;
                        }
                    }

                    return false;
                })
                ->sum(fn ($item) => (float) $item->solde_debiteur - (float) $item->solde_crediteur);

            return $brut($items) - $brut($itemsPrev);
        };

        $cafN = $this->calculerESG($items)['caf'];
        $cafP = $this->calculerESG($itemsPrev)['caf'];
        $cessionIncorpN = $this->montant($items, '7512', 'produit');
        $cessionIncorpP = $this->montant($itemsPrev, '7512', 'produit');
        $cessionCorpN = $this->montant($items, '7513', 'produit');
        $cessionCorpP = $this->montant($itemsPrev, '7513', 'produit');
        $cessionFinN = $this->montant($items, '7514', 'produit');
        $cessionFinP = $this->montant($itemsPrev, '7514', 'produit');
        $cessionsN = $cessionIncorpN + $cessionCorpN + $cessionFinN;
        $cessionsP = $cessionIncorpP + $cessionCorpP + $cessionFinP;
        $acqIncorp = max(0.0, $variationBrute(['22']));
        $acqCorp = max(0.0, $variationBrute(['23']));
        $acqFin = max(0.0, $variationBrute(['24', '25']));
        $emploisNonValeurs = max(0.0, $variationBrute(['21']));

        $fluxRows = [
            ['section' => "I- RESSOURCES STABLES DE L'EXERCICE (FLUX)"],
            ['subtotal' => true, 'label' => 'Autofinancement (A)', 'n_ressource' => $cafN, 'p_ressource' => $cafP],
            ['label' => '+ Capacité d\'autofinancement', 'n_ressource' => $cafN, 'p_ressource' => $cafP],
            ['label' => '- Distributions de bénéfices'],
            ['subtotal' => true, 'label' => "Cessions et réductions d'immobilisations (B)", 'n_ressource' => $cessionsN, 'p_ressource' => $cessionsP],
            ['label' => "+ Cessions d'immobilisations incorporelles", 'n_ressource' => $cessionIncorpN, 'p_ressource' => $cessionIncorpP],
            ['label' => "+ Cessions d'immobilisations corporelles", 'n_ressource' => $cessionCorpN, 'p_ressource' => $cessionCorpP],
            ['label' => "+ Cessions d'immobilisations financières", 'n_ressource' => $cessionFinN, 'p_ressource' => $cessionFinP],
            ['label' => '+ Récupérations sur créances immobilisées'],
            ['subtotal' => true, 'label' => 'Augmentation des capitaux propres et assimilés (C)'],
            ['label' => '+ Augmentation du capital, apports'],
            ['label' => "+ Subventions d'investissement"],
            ['subtotal' => true, 'label' => 'Augmentation des dettes de financement (D)'],
            ['total' => true, 'label' => 'TOTAL I - RESSOURCES STABLES', 'n_ressource' => $cafN + $cessionsN, 'p_ressource' => $cafP + $cessionsP],
            ['section' => "II- EMPLOIS STABLES DE L'EXERCICE (FLUX)"],
            ['subtotal' => true, 'label' => "Acquisitions et augmentations d'immobilisations (E)", 'n_emploi' => $acqIncorp + $acqCorp + $acqFin],
            ['label' => "Acquisitions d'immobilisations incorporelles", 'n_emploi' => $acqIncorp],
            ['label' => "Acquisitions d'immobilisations corporelles", 'n_emploi' => $acqCorp],
            ['label' => "Acquisitions d'immobilisations financières", 'n_emploi' => $acqFin],
            ['label' => 'Augmentation des créances immobilisées'],
            ['subtotal' => true, 'label' => 'Remboursement des capitaux propres (F)'],
            ['subtotal' => true, 'label' => 'Remboursements des dettes de financement (G)'],
            ['label' => 'Emplois en non-valeurs', 'n_emploi' => $emploisNonValeurs],
            ['total' => true, 'label' => 'TOTAL II - EMPLOIS STABLES', 'n_emploi' => $acqIncorp + $acqCorp + $acqFin + $emploisNonValeurs],
            ['label' => 'III- VARIATION DU BESOIN DE FINANCEMENT GLOBAL (B.F.G)', 'n_emploi' => $synthese[5]['emploi'], 'n_ressource' => $synthese[5]['ressource']],
            ['label' => 'IV- VARIATION DE LA TRÉSORERIE', 'n_emploi' => $synthese[6]['emploi'], 'n_ressource' => $synthese[6]['ressource']],
        ];

        $fluxTotal = (object) ['n_emploi' => 0.0, 'n_ressource' => 0.0, 'p_emploi' => 0.0, 'p_ressource' => 0.0];
        foreach ($fluxRows as $row) {
            if (!empty($row['section']) || !empty($row['total']) || !empty($row['subtotal'])) {
                continue;
            }
            foreach (get_object_vars($fluxTotal) as $key => $_) {
                $fluxTotal->{$key} += (float) ($row[$key] ?? 0);
            }
        }

        return compact('synthese', 'fluxRows', 'fluxTotal', 'exercice');
    }

    private function cpcRows(array $cpcData): array
    {
        $blank = fn () => (object) ['col1' => 0.0, 'col2' => 0.0, 'col3' => 0.0, 'col4' => 0.0];
        $sectionRows = function (string $prefix) use ($cpcData) {
            foreach ($cpcData as $section => $rows) {
                if (str_starts_with($section, $prefix)) {
                    return array_values($rows);
                }
            }

            return [];
        };
        $pick = fn (array $rows, int $index) => $rows[$index] ?? $blank();
        $row = fn (string $sectionPrefix, int $index) => $pick($sectionRows($sectionPrefix), $index);
        $single = fn (string $sectionPrefix) => $sectionRows($sectionPrefix)[0] ?? $blank();

        $sumRows = function (array $rows) {
            $total = ['col1' => 0.0, 'col2' => 0.0, 'col3' => 0.0, 'col4' => 0.0];
            foreach ($rows as $item) {
                foreach (array_keys($total) as $column) {
                    $total[$column] += (float) $item->{$column};
                }
            }

            return (object) $total;
        };

        $produitsExploitation = [$row('I.', 0), $row('I.', 1), $row('I.', 2), $row('I.', 3), $row('I.', 4), $row('I.', 5), $row('I.', 6)];
        $chargesExploitation = [$row('II.', 0), $row('II.', 1), $row('II.', 2), $row('II.', 3), $row('II.', 4), $row('II.', 5), $row('II.', 6)];
        $produitsFinanciers = [$row('IV.', 0), $row('IV.', 1), $row('IV.', 2), $row('IV.', 3)];
        $chargesFinancieres = [$row('V.', 0), $row('V.', 1), $row('V.', 2), $row('V.', 3)];
        $produitsNonCourants = [$row('VIII.', 0), $row('VIII.', 1), $row('VIII.', 2), $row('VIII.', 3), $row('VIII.', 4)];
        $chargesNonCourantes = [$row('IX.', 0), $row('IX.', 1), $row('IX.', 2), $row('IX.', 3)];

        $chiffresAffaires = $sumRows([$produitsExploitation[0], $produitsExploitation[1]]);
        $totalI = $sumRows([$chiffresAffaires, ...array_slice($produitsExploitation, 2)]);
        $totalII = $sumRows($chargesExploitation);
        $resultatIII = (object) ['col1' => 0.0, 'col2' => 0.0, 'col3' => $totalI->col3 - $totalII->col3, 'col4' => $totalI->col4 - $totalII->col4];
        $totalIV = $sumRows($produitsFinanciers);
        $totalV = $sumRows($chargesFinancieres);
        $resultatVI = (object) ['col1' => 0.0, 'col2' => 0.0, 'col3' => $totalIV->col3 - $totalV->col3, 'col4' => $totalIV->col4 - $totalV->col4];
        $resultatVII = (object) ['col1' => 0.0, 'col2' => 0.0, 'col3' => $resultatIII->col3 + $resultatVI->col3, 'col4' => $resultatIII->col4 + $resultatVI->col4];
        $totalVIII = $sumRows($produitsNonCourants);
        $totalIX = $sumRows($chargesNonCourantes);
        $resultatX = (object) ['col1' => 0.0, 'col2' => 0.0, 'col3' => $totalVIII->col3 - $totalIX->col3, 'col4' => $totalVIII->col4 - $totalIX->col4];
        $resultatXI = (object) ['col1' => 0.0, 'col2' => 0.0, 'col3' => $resultatVII->col3 + $resultatX->col3, 'col4' => $resultatVII->col4 + $resultatX->col4];
        $totalXII = $single('XII.');
        $resultatXIII = (object) ['col1' => 0.0, 'col2' => 0.0, 'col3' => $resultatXI->col3 - $totalXII->col3, 'col4' => $resultatXI->col4 - $totalXII->col4];
        $totalXIV = (object) ['col1' => 0.0, 'col2' => 0.0, 'col3' => $totalI->col3 + $totalIV->col3 + $totalVIII->col3, 'col4' => $totalI->col4 + $totalIV->col4 + $totalVIII->col4];
        $totalXV = (object) ['col1' => 0.0, 'col2' => 0.0, 'col3' => $totalII->col3 + $totalV->col3 + $totalIX->col3 + $totalXII->col3, 'col4' => $totalII->col4 + $totalV->col4 + $totalIX->col4 + $totalXII->col4];
        $resultatXVI = (object) ['col1' => 0.0, 'col2' => 0.0, 'col3' => $totalXIV->col3 - $totalXV->col3, 'col4' => $totalXIV->col4 - $totalXV->col4];

        return [
            ['label' => 'I. PRODUITS D\'EXPLOITATION', 'values' => $totalI],
            ['label' => '* Ventes de marchandises (en l\'état)', 'values' => $produitsExploitation[0]],
            ['label' => '* Ventes de biens et services produits', 'values' => $produitsExploitation[1]],
            ['label' => '* Chiffres d\'affaires', 'values' => $chiffresAffaires],
            ['label' => '* Variation de stocks de produits (1)', 'values' => $produitsExploitation[2]],
            ['label' => '* Immobilisations produites par l\'entreprise pour elle-même', 'values' => $produitsExploitation[3]],
            ['label' => '* Subventions d\'exploitation', 'values' => $produitsExploitation[4]],
            ['label' => '* Autres produits d\'exploitation', 'values' => $produitsExploitation[5]],
            ['label' => '* Reprises d\'exploitation : transferts de charges', 'values' => $produitsExploitation[6]],
            ['label' => 'Total I', 'values' => $totalI],
            ['label' => 'II. CHARGES D\'EXPLOITATION', 'values' => $totalII],
            ['label' => '* Achats revendus(2) de marchandises', 'values' => $chargesExploitation[0]],
            ['label' => '* Achats consommés(2) de matières et fournitures', 'values' => $chargesExploitation[1]],
            ['label' => '* Autres charges externes', 'values' => $chargesExploitation[2]],
            ['label' => '* Impôts et taxes', 'values' => $chargesExploitation[3]],
            ['label' => '* Charges de personnel', 'values' => $chargesExploitation[4]],
            ['label' => '* Autres charges d\'exploitation', 'values' => $chargesExploitation[5]],
            ['label' => '* Dotations d\'exploitation', 'values' => $chargesExploitation[6]],
            ['label' => 'Total II', 'values' => $totalII],
            ['label' => 'III. RESULTAT D\'EXPLOITATION (I- II)', 'values' => $resultatIII],
            ['label' => 'IV. PRODUITS FINANCIERS', 'values' => $totalIV],
            ['label' => '* Produits des titres de partic. et autres titres immobilisés', 'values' => $produitsFinanciers[0]],
            ['label' => '* Gains de change', 'values' => $produitsFinanciers[1]],
            ['label' => '* Intérêts et autres produits financiers', 'values' => $produitsFinanciers[2]],
            ['label' => '* Reprises financier : transfert charges', 'values' => $produitsFinanciers[3]],
            ['label' => '. Total IV', 'values' => $totalIV],
            ['label' => 'V. CHARGES FINANCIERES', 'values' => $totalV],
            ['label' => '* Charges d\'intérêts', 'values' => $chargesFinancieres[0]],
            ['label' => '* Pertes de change', 'values' => $chargesFinancieres[1]],
            ['label' => '* Autres charges financières', 'values' => $chargesFinancieres[2]],
            ['label' => '* Dotations financières', 'values' => $chargesFinancieres[3]],
            ['label' => 'Total V', 'values' => $totalV],
            ['label' => 'VI. RESULTAT FINANCIER (IV-V)', 'values' => $resultatVI],
            ['label' => 'VII. RESULTAT COURANT (III+VI)', 'values' => $resultatVII],
            ['label' => 'VIII. PRODUITS NON COURANTS', 'values' => $totalVIII],
            ['label' => '* Produits des cessions d\'immobilisations', 'values' => $produitsNonCourants[0]],
            ['label' => '* Subventions d\'équilibre', 'values' => $produitsNonCourants[1]],
            ['label' => '* Reprises sur subventions d\'investissement', 'values' => $produitsNonCourants[2]],
            ['label' => '* Autres produits non courants', 'values' => $produitsNonCourants[3]],
            ['label' => '* Reprises non courantes ; transferts de charges', 'values' => $produitsNonCourants[4]],
            ['label' => 'Total VIII', 'values' => $totalVIII],
            ['label' => 'IX. CHARGES NON COURANTES', 'values' => $totalIX],
            ['label' => '* Valeurs nettes d\'amortissements des immobilisations cédées', 'values' => $chargesNonCourantes[0]],
            ['label' => '* Subventions accordées', 'values' => $chargesNonCourantes[1]],
            ['label' => '* Autres charges non courantes', 'values' => $chargesNonCourantes[2]],
            ['label' => '* Dotations non courantes aux amortissements et aux provisions', 'values' => $chargesNonCourantes[3]],
            ['label' => 'Total IX', 'values' => $totalIX],
            ['label' => 'X. RESULTAT NON COURANT (VIII- IX)', 'values' => $resultatX],
            ['label' => 'XI. RESULTAT AVANT IMPOTS (VII+X)', 'values' => $resultatXI],
            ['label' => 'XII. IMPOTS SUR LES RESULTATS', 'values' => $totalXII],
            ['label' => 'RESULTAT NET (XI-XII)', 'values' => $resultatXIII],
            ['label' => 'XIV. TOTAL DES PRODUITS (I+IV+VIII)', 'values' => $totalXIV],
            ['label' => 'XV. TOTAL DES CHARGES (II+V+IX+XII)', 'values' => $totalXV],
            ['label' => 'RESULTAT NET (total des produits- total des charges)', 'values' => $resultatXVI],
        ];
    }

    public function montant(Collection $items, $prefixes, string $type): float
    {
        $prefixes = (array) $prefixes;

        return (float) $items->filter(function ($item) use ($prefixes) {
            foreach ($prefixes as $prefix) {
                if (str_starts_with((string) $item->compte, $prefix)) {
                    return true;
                }
            }

            return false;
        })->sum(fn ($item) => $type === 'produit'
            ? (float) $item->solde_crediteur - (float) $item->solde_debiteur
            : (float) $item->solde_debiteur - (float) $item->solde_crediteur);
    }

    private function calculerLigneActif(Collection $items, $codesBrut, $codesAmort, ?Collection $itemsPrev = null): object
    {
        $brutAmortNet = function (Collection $collection) use ($codesBrut, $codesAmort) {
            $codesBrut = (array) $codesBrut;
            $codesAmort = (array) $codesAmort;

            $brut = (float) $collection->filter(function ($item) use ($codesBrut) {
                foreach ($codesBrut as $code) {
                    if (str_starts_with((string) $item->compte, $code)
                        && !str_starts_with((string) $item->compte, '28')
                        && !str_starts_with((string) $item->compte, '29')) {
                        return true;
                    }
                }

                return false;
            })->sum(fn ($item) => (float) $item->solde_debiteur - (float) $item->solde_crediteur);

            $amort = 0.0;
            if ($codesAmort !== []) {
                $amort = (float) $collection->filter(function ($item) use ($codesAmort) {
                    foreach ($codesAmort as $code) {
                        if (str_starts_with((string) $item->compte, $code)) {
                            return true;
                        }
                    }

                    return false;
                })->sum(fn ($item) => (float) $item->solde_crediteur - (float) $item->solde_debiteur);
            }

            return [
                'brut' => $brut < 0 ? abs($brut) : $brut,
                'amort' => $amort < 0 ? abs($amort) : $amort,
                'net' => ($brut < 0 ? abs($brut) : $brut) - ($amort < 0 ? abs($amort) : $amort),
            ];
        };

        $courant = $brutAmortNet($items);
        $netPrecedent = $itemsPrev === null ? 0.0 : $brutAmortNet($itemsPrev)['net'];

        return (object) [
            'brut' => $courant['brut'],
            'amort' => $courant['amort'],
            'net' => $courant['net'],
            'net_prec' => $netPrecedent,
        ];
    }

    private function calculerLignePassif(Collection $items, $codes, ?Collection $itemsPrev = null): object
    {
        $codes = (array) $codes;
        $calcul = fn (?Collection $collection): float => $collection === null ? 0.0 : (float) $collection
            ->filter(function ($item) use ($codes) {
                foreach ($codes as $code) {
                    if (str_starts_with((string) $item->compte, $code)) {
                        return true;
                    }
                }

                return false;
            })
            ->sum(fn ($item) => (float) $item->solde_crediteur - (float) $item->solde_debiteur);

        return (object) [
            'montant' => $calcul($items),
            'montant_prec' => $calcul($itemsPrev),
        ];
    }

    private function sommerRubriquesActif(array $data, array $rubriques): object
    {
        $brut = 0.0;
        $amort = 0.0;
        $net = 0.0;
        $netPrec = 0.0;

        foreach ($rubriques as $rubrique) {
            foreach ($data[$rubrique] ?? [] as $ligne) {
                $brut += (float) $ligne->brut;
                $amort += (float) $ligne->amort;
                $net += (float) $ligne->net;
                $netPrec += (float) ($ligne->net_prec ?? 0);
            }
        }

        return (object) ['brut' => $brut, 'amort' => $amort, 'net' => $net, 'net_prec' => $netPrec];
    }

    private function sommerRubriquesPassif(array $data, array $rubriques): object
    {
        $total = 0.0;
        $totalPrec = 0.0;

        foreach ($rubriques as $rubrique) {
            foreach ($data[$rubrique] ?? [] as $ligne) {
                $total += (float) $ligne->montant;
                $totalPrec += (float) ($ligne->montant_prec ?? 0);
            }
        }

        return (object) ['montant' => $total, 'montant_prec' => $totalPrec];
    }

    private function calculateRow(Collection $items, Collection $itemsPrev, $codes): object
    {
        $codes = (array) $codes;

        $montant = function (Collection $collection) use ($codes): float {
            $filtered = $collection->filter(function ($item) use ($codes) {
                foreach ($codes as $code) {
                    if (str_starts_with((string) $item->compte, $code)) {
                        return true;
                    }
                }

                return false;
            });

            $isProduit = str_starts_with((string) $codes[0], '7');

            return (float) $filtered->sum(fn ($item) => $isProduit
                ? (float) $item->solde_crediteur - (float) $item->solde_debiteur
                : (float) $item->solde_debiteur - (float) $item->solde_crediteur);
        };

        $currentItems = $items->filter(function ($item) use ($codes) {
            foreach ($codes as $code) {
                if (str_starts_with((string) $item->compte, $code)) {
                    return true;
                }
            }

            return false;
        });

        $precedent = $currentItems->filter(fn ($item) => strlen((string) $item->compte) >= 4
            && str_starts_with(substr((string) $item->compte, 3, 1), '8'));
        $propres = $currentItems->diff($precedent);

        $col1 = $montant($propres);
        $col2 = $montant($precedent);

        return (object) [
            'col1' => $col1,
            'col2' => $col2,
            'col3' => $col1 + $col2,
            'col4' => $montant($itemsPrev),
        ];
    }

    private function calculerLigneImmo(Collection $items, string $codePrefixe, ?Collection $itemsPrev = null, array $excludePrefixes = []): object
    {
        $brut = function (?Collection $collection) use ($codePrefixe, $excludePrefixes): float {
            if ($collection === null || $collection->isEmpty()) {
                return 0.0;
            }

            $solde = (float) $collection->filter(function ($item) use ($codePrefixe, $excludePrefixes) {
                $compte = (string) $item->compte;

                foreach ($excludePrefixes as $excludePrefix) {
                    if (str_starts_with($compte, $excludePrefix)) {
                        return false;
                    }
                }

                return str_starts_with($compte, $codePrefixe)
                    && !str_starts_with($compte, '28')
                    && !str_starts_with($compte, '29');
            })
                ->sum(fn ($item) => (float) $item->solde_debiteur - (float) $item->solde_crediteur);

            return $solde < 0 ? abs($solde) : $solde;
        };

        $debut = $brut($itemsPrev);
        $brutN = $brut($items);
        $variation = $brutN - $debut;
        $acquisition = $variation > 0 ? $variation : 0.0;
        $cession = $variation < 0 ? abs($variation) : 0.0;

        return (object) [
            'debut' => $debut,
            'acquisition' => $acquisition,
            'production' => 0.0,
            'virement_aug' => 0.0,
            'cession' => $cession,
            'retrait' => 0.0,
            'virement_dim' => 0.0,
            'fin' => $debut + $acquisition - $cession,
        ];
    }

    private function calculerLigneAmortissement(Collection $items, Collection $itemsPrev, string $codeAmort, string $codeDotationPrefixe): object
    {
        $cumul = fn (Collection $collection): float => max(0.0, (float) $collection
            ->filter(fn ($item) => str_starts_with((string) $item->compte, $codeAmort))
            ->sum(fn ($item) => (float) $item->solde_crediteur - (float) $item->solde_debiteur));

        $cumulDebut = $cumul($itemsPrev);
        $cumulFin = $cumul($items);
        $dotationComptable = max(0.0, (float) $items
            ->filter(fn ($item) => str_starts_with((string) $item->compte, $codeDotationPrefixe))
            ->sum(fn ($item) => (float) $item->solde_debiteur - (float) $item->solde_crediteur));
        $dotationExercice = $dotationComptable > 0 ? $dotationComptable : max(0.0, $cumulFin - $cumulDebut);
        $sorties = max(0.0, $cumulDebut + $dotationExercice - $cumulFin);

        return (object) [
            'col1' => $cumulDebut,
            'col2' => $dotationExercice,
            'col3' => $sorties,
            'col4' => $cumulFin,
        ];
    }

    private function calculerESG(Collection $items): array
    {
        $m = fn ($prefixes, string $type) => $this->montant($items, $prefixes, $type);

        $ventesMarch = $m('711', 'produit');
        $achatsRevendus = $m('611', 'charge');
        $margeBrute = $ventesMarch - $achatsRevendus;
        $ventesBiens = $m('712', 'produit');
        $varStock = $m('713', 'produit');
        $immobProduites = $m('714', 'produit');
        $production = $ventesBiens + $varStock + $immobProduites;
        $achatsConsommes = $m('612', 'charge');
        $autresChargesExt = $m(['613', '614'], 'charge');
        $consommation = $achatsConsommes + $autresChargesExt;
        $va = $margeBrute + $production - $consommation;
        $subvExpl = $m('716', 'produit');
        $impotsTaxes = $m('616', 'charge');
        $chargesPersonnel = $m('617', 'charge');
        $ebe = $va + $subvExpl - $impotsTaxes - $chargesPersonnel;
        $autresProdExpl = $m('718', 'produit');
        $autresChargesExpl = $m('618', 'charge');
        $reprisesExpl = $m('719', 'produit');
        $dotationsExpl = $m('619', 'charge');
        $resExpl = $ebe + $autresProdExpl - $autresChargesExpl + $reprisesExpl - $dotationsExpl;
        $prodFin = $m('73', 'produit');
        $chargesFin = $m('63', 'charge');
        $resFin = $prodFin - $chargesFin;
        $resCourant = $resExpl + $resFin;
        $prodNC = $m('75', 'produit');
        $chargesNC = $m('65', 'charge');
        $resNC = $prodNC - $chargesNC;
        $impotsResultats = $m('67', 'charge');
        $resNet = $resCourant + $resNC - $impotsResultats;
        $dotFin = $m('639', 'charge');
        $dotNC = $m('659', 'charge');
        $reprFin = $m('739', 'produit');
        $reprNC = $m('759', 'produit');
        $produitsCession = $m('751', 'produit');
        $vnaCedees = $m('651', 'charge');
        $caf = $resNet + $dotationsExpl + $dotFin + $dotNC - $reprisesExpl - $reprFin - $reprNC - $produitsCession + $vnaCedees;
        $distributions = 0.0;

        return compact(
            'ventesMarch', 'achatsRevendus', 'margeBrute', 'ventesBiens', 'varStock', 'immobProduites',
            'production', 'achatsConsommes', 'autresChargesExt', 'consommation', 'va', 'subvExpl',
            'ebe', 'impotsTaxes', 'chargesPersonnel', 'autresProdExpl', 'autresChargesExpl', 'reprisesExpl',
            'dotationsExpl', 'resExpl', 'resFin', 'resCourant', 'resNC', 'impotsResultats', 'resNet',
            'dotFin', 'dotNC', 'reprFin', 'reprNC', 'produitsCession', 'vnaCedees', 'caf', 'distributions'
        ) + [
            'benefice' => $resNet > 0 ? $resNet : 0.0,
            'perte' => $resNet < 0 ? abs($resNet) : 0.0,
            'autofinancement' => $caf - $distributions,
        ];
    }

    private function calculerLigneStock(Collection $items, Collection $itemsPrev, array|string $codesBrut, array|string $codesProvision): object
    {
        $codesBrut = (array) $codesBrut;
        $codesProvision = (array) $codesProvision;
        $filtre = function ($item, array $codes): bool {
            foreach ($codes as $code) {
                if (str_starts_with((string) $item->compte, $code)) {
                    return true;
                }
            }

            return false;
        };
        $calcul = function (Collection $collection) use ($codesBrut, $codesProvision, $filtre): array {
            $brut = (float) $collection->filter(fn ($item) => $filtre($item, $codesBrut))
                ->sum(fn ($item) => (float) $item->solde_debiteur - (float) $item->solde_crediteur);
            $provision = (float) $collection->filter(fn ($item) => $filtre($item, $codesProvision))
                ->sum(fn ($item) => (float) $item->solde_crediteur - (float) $item->solde_debiteur);

            return ['brut' => $brut, 'provision' => $provision, 'net' => $brut - $provision];
        };

        $final = $calcul($items);
        $initial = $calcul($itemsPrev);

        return (object) [
            'final_brut' => $final['brut'],
            'final_provision' => $final['provision'],
            'final_net' => $final['net'],
            'initial_brut' => $initial['brut'],
            'initial_provision' => $initial['provision'],
            'initial_net' => $initial['net'],
            'variation' => $final['brut'] - $initial['brut'],
        ];
    }

    private function totaliserLignesStock(iterable $lignes): object
    {
        $total = (object) [
            'final_brut' => 0.0, 'final_provision' => 0.0, 'final_net' => 0.0,
            'initial_brut' => 0.0, 'initial_provision' => 0.0, 'initial_net' => 0.0,
            'variation' => 0.0,
        ];

        foreach ($lignes as $ligne) {
            if ($ligne === null) {
                continue;
            }
            foreach (array_keys(get_object_vars($total)) as $champ) {
                $total->{$champ} += (float) $ligne->{$champ};
            }
        }

        return $total;
    }

    private function sumObjectRows(iterable $rows, array $columns): object
    {
        $total = array_fill_keys($columns, 0.0);
        foreach ($rows as $row) {
            foreach ($columns as $column) {
                $total[$column] += (float) ($row->{$column} ?? 0);
            }
        }

        return (object) $total;
    }
}
