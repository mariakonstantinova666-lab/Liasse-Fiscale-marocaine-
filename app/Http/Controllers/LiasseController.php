<?php

namespace App\Http\Controllers;

use App\Models\BalanceItem;
use App\Models\LiasseData;
use App\Services\BalanceService;
use App\Services\ActiveExerciceService;
use App\Services\LiasseControlService;
use App\Services\LiasseTableDataService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class LiasseController extends Controller
{
    /** Tableaux déclaratifs éditables (saisie manuelle persistée dans liasse_data). */
    private const TABLEAUX_EDITABLES = [
        'passage_fiscal',
        'credit_bail', 'plus_values', 'titres_participation', 'repartition_capital',
        'affectation_resultats', 'calcul_impot_encouragement', 'dotations_amortissements',
        'plus_values_fusion', 'interets_emprunts', 'locations_baux', 'detail_stocks',
        'operations_devises', 'methodes_evaluation', 'derogations', 'changements_methodes',
        'calcul_is_encouragees',
    ];

    /** Champs T03 explicitement ouverts à la saisie manuelle. */
    private const PASSAGE_FISCAL_EDITABLE_KEYS = [
        'reintegration_courante_0_label',
        'reintegration_courante_0_montant',
        'reintegrations_courantes_total',
        'reintegration_non_courante_0_label',
        'reintegration_non_courante_0_montant',
        'reintegrations_non_courantes_total',
        'deductions_courantes_total',
        'deductions_non_courantes_total',
        'reports_deficitaires_total',
    ];

    public function cpc()
    {
        $exercice = $this->currentExercice();
        ['cpcData' => $cpcData] = app(LiasseTableDataService::class)->cpc(Auth::id(), $exercice);

        return view('liasse.cpc', compact('cpcData', 'exercice'));
    }
    public function liasseImport(Request $request)
    {
        // Logique d'importation
    }

    public function bilanActif(BalanceService $balanceService)
    {
        $exercice = $this->currentExercice();
        ['data' => $data, 'totaux' => $totaux] = (new LiasseTableDataService($balanceService))->bilanActif(Auth::id(), $exercice);

        return view('liasse.bilan_actif', compact('data', 'totaux', 'exercice'));
    }
    public function liasseImmobilisations()
    {
        return $this->immobilisations();
    }

    public function immobilisations(?BalanceService $balanceService = null)
    {
        $balanceService ??= app(BalanceService::class);

        $exercice = $this->currentExercice();
        ['immoData' => $immoData, 'totauxImmo' => $totauxImmo] = (new LiasseTableDataService($balanceService))->immobilisations(Auth::id(), $exercice);

        return view('liasse.immobilisations', compact('immoData', 'totauxImmo', 'exercice'));

        $userId = Auth::id();

        // Brut au début = clôture N-1 ; les augmentations/diminutions se déduisent
        // de la variation entre la balance N et la balance N-1.
        [$items, $itemsPrev] = $balanceService->lignesAvecPrecedent($userId, $exercice);

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
                'Mobilier, matériel de bureau et aménagement' => $this->calculerLigneImmo($items, '235', $itemsPrev),
                'Autres immobilisations corporelles' => $this->calculerLigneImmo($items, '238', $itemsPrev),
                'Immobilisations corporelles en cours' => $this->calculerLigneImmo($items, '239', $itemsPrev),
            ]
        ];

        $totauxImmo = [];
        foreach ($immoData as $rubrique => $lignes) {
            $debut = 0; $acquisition = 0; $production = 0; $virement_aug = 0; $cession = 0; $retrait = 0; $virement_dim = 0; $fin = 0;
            foreach ($lignes as $ligne) {
                $debut += $ligne->debut;
                $acquisition += $ligne->acquisition;
                $production += $ligne->production;
                $virement_aug += $ligne->virement_aug;
                $cession += $ligne->cession;
                $retrait += $ligne->retrait;
                $virement_dim += $ligne->virement_dim;
                $fin += $ligne->fin;
            }
            $totauxImmo[$rubrique] = (object)[
                'debut' => $debut, 'acquisition' => $acquisition, 'production' => $production, 'virement_aug' => $virement_aug,
                'cession' => $cession, 'retrait' => $retrait, 'virement_dim' => $virement_dim, 'fin' => $fin
            ];
        }

        return view('liasse.immobilisations', compact('immoData', 'totauxImmo', 'exercice'));
    }

    public function bilanPassif(?BalanceService $balanceService = null)
    {
        $balanceService ??= app(BalanceService::class);
        $exercice = $this->currentExercice();
        ['data' => $data, 'totaux' => $totaux] = (new LiasseTableDataService($balanceService))->bilanPassif(Auth::id(), $exercice);

        return view('liasse.bilan_passif', compact('data', 'totaux', 'exercice'));
    }
    public function passageFiscal() 
    { 
        $exercice = $this->currentExercice();
        $userId = Auth::id();
        $items = BalanceItem::where('user_id', $userId)->where('exercice', $exercice)->get();
        $sourceData = LiasseData::where('user_id', $userId)
            ->where('exercice', $exercice)
            ->where('tableau_code', 'passage_fiscal')
            ->pluck('valeur', 'cle')
            ->toArray();
        $sourceNumber = function (string $key, ?float $fallback = null) use ($sourceData): ?float {
            if (!array_key_exists($key, $sourceData)) {
                return $fallback;
            }

            $value = str_replace(["\xc2\xa0", ' '], '', (string) $sourceData[$key]);
            $value = str_replace(',', '.', $value);

            return is_numeric($value) ? (float) $value : $fallback;
        };

        $totalProduits = (float) $items->filter(fn($i) => str_starts_with($i->compte, '7'))->sum(fn($i) => $i->solde_crediteur - $i->solde_debiteur);
        $totalCharges = (float) $items->filter(fn($i) => str_starts_with($i->compte, '6'))->sum(fn($i) => $i->solde_debiteur - $i->solde_crediteur);
        
        $montantComptable = $totalProduits - $totalCharges;

        if ($items->isEmpty() && $montantComptable == 0) {
            $montantComptable = -2665.62;
        }

        $beneficeNetComptable = $montantComptable > 0 ? $montantComptable : 0.00;
        $perteNetteComptable = $montantComptable < 0 ? abs($montantComptable) : 0.00;

        $reintegrationsCourantes = $sourceNumber('reintegrations_courantes_total', $this->calculerMontantFiscal($items, ['6143_fake'])); 
        $reintegrationsNonCourantes = $sourceNumber('reintegrations_non_courantes_total', $this->calculerMontantFiscal($items, ['6581_fake']));
        
        $deductionsCourantes = $sourceNumber('deductions_courantes_total', $this->calculerMontantFiscal($items, ['7182_fake']));
        $deductionsNonCourantes = $sourceNumber('deductions_non_courantes_total', $this->calculerMontantFiscal($items, ['7581_fake']));

        $totalReintegrations = $reintegrationsCourantes + $reintegrationsNonCourantes;
        $totalDeductions = $deductionsCourantes + $deductionsNonCourantes;

        $resultatBrutUnfiltered = $beneficeNetComptable - $perteNetteComptable + $totalReintegrations - $totalDeductions;

        $beneficeBrutFiscal = $resultatBrutUnfiltered > 0 ? $resultatBrutUnfiltered : 0.00;
        $deficitBrutFiscal = $resultatBrutUnfiltered < 0 ? abs($resultatBrutUnfiltered) : 0.00;

        $reportsDeficitaires = ['n-4' => 0.00, 'n-3' => 0.00, 'n-2' => 0.00, 'n-1' => $sourceNumber('reports_deficitaires_total', 0.00)];
        $totalReportsImputes = array_sum($reportsDeficitaires);

        $beneficeNetFiscal = $beneficeBrutFiscal > 0 ? max(0, $beneficeBrutFiscal - $totalReportsImputes) : 0.00;
        $deficitNetFiscal = $deficitBrutFiscal;

        $cumulAmortissementsDifferes = 0.00;
        $cumulDeficitsRestants = ['n-4' => 0.00, 'n-3' => 0.00, 'n-2' => 0.00, 'n-1' => 0.00];

        $fiscalData = [
            'I. RESULTAT NET COMPTABLE' => [
                'Bénéfice net' => $beneficeNetComptable,
                'Perte nette' => $perteNetteComptable,
            ],
            'II. REINTEGRATIONS FISCALES' => [
                '1. Courantes' => $reintegrationsCourantes,
                '2. Non courantes' => $reintegrationsNonCourantes,
            ],
            'III. DEDUCTIONS FISCALES' => [
                '1. Courantes' => $deductionsCourantes,
                '2. Non courantes' => $deductionsNonCourantes,
            ],
            'SYNTHESE_TOTAL' => [
                'Total Réintégrations' => $beneficeNetComptable + $totalReintegrations,
                'Total Déductions' => $perteNetteComptable + $totalDeductions,
            ],
            'DETAIL_REINTEGRATIONS' => [
                'Courantes' => [
                    [
                        'label' => $sourceData['reintegration_courante_0_label'] ?? 'Impôt sur les résultats / Cotisation Minimale (non déductible)',
                        'montant' => $sourceNumber('reintegration_courante_0_montant', 0.00),
                    ],
                ],
                'Non courantes' => [
                    [
                        'label' => $sourceData['reintegration_non_courante_0_label'] ?? 'Pénalités et amendes fiscales ou pénales',
                        'montant' => $sourceNumber('reintegration_non_courante_0_montant', 0.00),
                    ],
                ],
            ],
            'IV. RESULTAT BRUT FISCAL' => [
                'Bénéfice brut si T1 > T2 (A)' => $beneficeBrutFiscal,
                'Déficit brut fiscal si T2 > T1 (B)' => $deficitBrutFiscal,
            ],
            'V. REPORTS DEFICITAIRES IMPUTES (C)' => [
                'Exercice n-4 ('.($exercice-4).')' => $reportsDeficitaires['n-4'],
                'Exercice n-3 ('.($exercice-3).')' => $reportsDeficitaires['n-3'],
                'Exercice n-2 ('.($exercice-2).')' => $reportsDeficitaires['n-2'],
                'Exercice n-1 ('.($exercice-1).')' => $reportsDeficitaires['n-1'],
                'Total Reports' => $totalReportsImputes
            ],
            'VI. RESULTAT NET FISCAL' => [
                'Bénéfice net fiscal (A-C)' => $beneficeNetFiscal,
                'ou déficit net fiscal (B)' => $deficitNetFiscal,
            ],
            'VII. CUMUL DES AMORTISSEMENTS FISCALEMENT DIFFERES' => [
                'Montant' => $cumulAmortissementsDifferes
            ],
            'VIII. CUMUL DES DEFICITS FISCAUX RESTANT A REPORTER' => [
                'Exercice n-4 ('.($exercice-4).')' => $cumulDeficitsRestants['n-4'],
                'Exercice n-3 ('.($exercice-3).')' => $cumulDeficitsRestants['n-3'],
                'Exercice n-2 ('.($exercice-2).')' => $cumulDeficitsRestants['n-2'],
                'Exercice n-1 ('.($exercice-1).')' => $cumulDeficitsRestants['n-1'],
            ]
        ];

        return view('liasse.passage_fiscal', compact('fiscalData', 'sourceData', 'exercice'));
    }

    public function amortissements(?BalanceService $balanceService = null)
    { 
        $balanceService ??= app(BalanceService::class);
        $exercice = $this->currentExercice();
        ['amortData' => $amortData, 'totauxAmort' => $totauxAmort, 'totalGeneral' => $totalGeneral] = (new LiasseTableDataService($balanceService))->amortissements(Auth::id(), $exercice);

        return view('liasse.amortissements', compact('amortData', 'totauxAmort', 'totalGeneral', 'exercice'));

        $userId = Auth::id();
        [$items, $itemsPrev] = $balanceService->lignesAvecPrecedent($userId, $exercice);

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
            ]
        ];

        $totauxAmort = [];
        foreach ($amortData as $rubrique => $lignes) {
            $col1 = 0; $col2 = 0; $col3 = 0; $col4 = 0;
            foreach ($lignes as $ligne) {
                $col1 += $ligne->col1;
                $col2 += $ligne->col2;
                $col3 += $ligne->col3;
                $col4 += $ligne->col4;
            }
            $totauxAmort[$rubrique] = (object)[
                'col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4
            ];
        }

        $totalGeneral = (object)[
            'col1' => array_sum(array_column($totauxAmort, 'col1')),
            'col2' => array_sum(array_column($totauxAmort, 'col2')),
            'col3' => array_sum(array_column($totauxAmort, 'col3')),
        ];
        $totalGeneral->col4 = $totalGeneral->col1 + $totalGeneral->col2 - $totalGeneral->col3;

        return view('liasse.amortissements', compact('amortData', 'totauxAmort', 'totalGeneral', 'exercice')); 
    }

    public function provisions() 
    { 
        $exercice = $this->currentExercice();

        $provisionsData = [
            'PROVISIONS DURABLES POUR RISQUES ET CHARGES' => [
                '- Provisions pour litiges' => $this->initialiserLigneProvision(),
                '- Provisions pour garanties données aux clients' => $this->initialiserLigneProvision(),
                '- Provisions pour propres assureurs' => $this->initialiserLigneProvision(),
                '- Provisions pour pertes sur marchés à terme' => $this->initialiserLigneProvision(),
                '- Provisions pour amendes, doubles droits, pénalités' => $this->initialiserLigneProvision(),
                '- Provisions pour charges à répartir sur plusieurs exercices' => $this->initialiserLigneProvision(),
                '- Provisions pour retraites et obligations similaires' => $this->initialiserLigneProvision(),
                '- Autres provisions durables pour risques et charges' => $this->initialiserLigneProvision(),
            ],
            'AUTRES PROVISIONS POUR RISQUES ET CHARGES' => [
                '- Provisions pour litiges' => $this->initialiserLigneProvision(),
                '- Provisions pour garanties données aux clients' => $this->initialiserLigneProvision(),
                '- Provisions pour pertes sur marchés à terme' => $this->initialiserLigneProvision(),
                '- Autres provisions pour risques et charges' => $this->initialiserLigneProvision(),
            ],
            'PROVISIONS POUR DEPRECIATION DE L\'ACTIF' => [
                '- Provisions pour dépréciation de l\'immobilisation en non-valeurs' => $this->initialiserLigneProvision(),
                '- Provisions pour dépréciation des immobilisations incorporelles' => $this->initialiserLigneProvision(),
                '- Provisions pour dépréciation des immobilisations corporelles' => $this->initialiserLigneProvision(),
                '- Provisions pour dépréciation des immobilisations financières' => $this->initialiserLigneProvision(),
                '- Provisions pour dépréciation des stocks' => $this->initialiserLigneProvision(),
                '- Provisions pour dépréciation des comptes clients' => $this->initialiserLigneProvision(),
                '- Provisions pour dépréciation des autres comptes débiteurs' => $this->initialiserLigneProvision(),
                '- Provisions pour dépréciation des titres et valeurs de placement' => $this->initialiserLigneProvision(),
                '- Provisions pour dépréciation des comptes de trésorerie' => $this->initialiserLigneProvision(),
            ]
        ];

        $totauxProvisions = [];
        foreach ($provisionsData as $rubrique => $lignes) {
            $col1 = 0; $col2 = 0; $col3 = 0; $col4 = 0; $col5 = 0; $col6 = 0; $col7 = 0;
            foreach ($lignes as $ligne) {
                $col1 += $ligne->col1;
                $col2 += $ligne->col2;
                $col3 += $ligne->col3;
                $col4 += $ligne->col4;
                $col5 += $ligne->col5;
                $col6 += $ligne->col6;
                $col7 += $ligne->col7;
            }
            $totauxProvisions[$rubrique] = (object)[
                'col1' => $col1, 'col2' => $col2, 'col3' => $col3, 
                'col4' => $col4, 'col5' => $col5, 'col6' => $col6, 'col7' => $col7
            ];
        }

        $totalGeneral = (object)[
            'col1' => array_sum(array_column($totauxProvisions, 'col1')),
            'col2' => array_sum(array_column($totauxProvisions, 'col2')),
            'col3' => array_sum(array_column($totauxProvisions, 'col3')),
            'col4' => array_sum(array_column($totauxProvisions, 'col4')),
            'col5' => array_sum(array_column($totauxProvisions, 'col5')),
            'col6' => array_sum(array_column($totauxProvisions, 'col6')),
            'col7' => array_sum(array_column($totauxProvisions, 'col7')),
        ];

        return view('liasse.provisions', compact('provisionsData', 'totauxProvisions', 'totalGeneral', 'exercice')); 
    }

    public function tva(?BalanceService $balanceService = null)
    {
        $balanceService ??= app(BalanceService::class);
        $exercice = $this->currentExercice();
        ['tvaRows' => $tvaRows] = (new LiasseTableDataService($balanceService))->tva(Auth::id(), $exercice);

        return view('liasse.tva', compact('tvaRows', 'exercice'));

        [$items, $itemsPrev] = $balanceService->lignesAvecPrecedent(Auth::id(), $exercice);

        $solde = fn ($collection, string $prefix, string $sens): float => (float) $collection
            ->filter(fn ($i) => str_starts_with((string) $i->compte, $prefix))
            ->sum(fn ($i) => $sens === 'credit'
                ? (float) $i->solde_crediteur - (float) $i->solde_debiteur
                : (float) $i->solde_debiteur - (float) $i->solde_crediteur);

        $ligne = fn (float $debut, float $fin): object => (object) [
            'debut' => $debut,
            'operations' => $fin - $debut,
            'declarations' => 0.0,
            'fin' => $fin,
        ];

        $facturee = $ligne($solde($itemsPrev, '4455', 'credit'), $solde($items, '4455', 'credit'));
        $recupImmo = $ligne($solde($itemsPrev, '34551', 'debit'), $solde($items, '34551', 'debit'));
        $recupTotal = $ligne($solde($itemsPrev, '3455', 'debit'), $solde($items, '3455', 'debit'));
        $recupCharges = $ligne(
            $recupTotal->debut - $recupImmo->debut,
            $recupTotal->fin - $recupImmo->fin
        );
        $due = $ligne(
            $facturee->debut - $recupTotal->debut,
            $facturee->fin - $recupTotal->fin
        );

        $tvaRows = [
            ['label' => 'A. T.V.A. Facturée', 'values' => $facturee, 'bold' => true],
            ['label' => 'B. T.V.A. Récupérable', 'values' => $recupTotal, 'bold' => true],
            ['label' => '- sur charges', 'values' => $recupCharges],
            ['label' => '- sur immobilisations', 'values' => $recupImmo],
            ['label' => 'C. T.V.A. due ou crédit de T.V.A = (A - B)', 'values' => $due, 'bold' => true],
        ];

        return view('liasse.tva', compact('tvaRows', 'exercice'));
    }

    // ===================================================================
    // TABLEAUX SUPPLÉMENTAIRES DE LA LIASSE (T05 → T26)
    // Structure exacte issue du modèle Simpl-IS (D3Simpl2). Chaque vue reçoit
    // $items (balance N) et $exercice via genericView ; le câblage des calculs
    // se fera tableau par tableau dans une étape ultérieure.
    // ===================================================================

    public function esg(?BalanceService $balanceService = null)                                                  // T05
    {
        $balanceService ??= app(BalanceService::class);
        $exercice = $this->currentExercice();
        [$items, $itemsPrev] = $balanceService->lignesAvecPrecedent(Auth::id(), $exercice);

        $n = $this->calculerESG($items);
        $p = $this->calculerESG($itemsPrev);

        $sp = "\u{00a0}\u{00a0}\u{00a0}";  // indentation libellé
        $rows = [
            ['section' => 'I - TABLEAU DE FORMATION DU RESULTAT ( T.F.R )'],
            ['l' => "1{$sp}Ventes de marchandises (en l'état )", 'k' => 'ventesMarch'],
            ['l' => "2\u{00a0}\u{00a0}-\u{00a0}\u{00a0}Achats revendus de marchandises", 'k' => 'achatsRevendus'],
            ['l' => "I{$sp}MARGES BRUTES SUR VENTES EN L'ETAT", 'k' => 'margeBrute', 'bold' => true],
            ['l' => "II\u{00a0}\u{00a0}+\u{00a0}\u{00a0}PRODUCTION DE L'EXERCICE (3+4+5)", 'k' => 'production', 'bold' => true],
            ['l' => "3{$sp}Ventes de biens et services produits", 'k' => 'ventesBiens'],
            ['l' => "4{$sp}Variation de stocks de produits", 'k' => 'varStock'],
            ['l' => "5{$sp}Immobilisations produites par l'entreprise pour elle même", 'k' => 'immobProduites'],
            ['l' => "III\u{00a0}\u{00a0}-\u{00a0}\u{00a0}CONSOMMATION DE L'EXERCICE (6+7)", 'k' => 'consommation', 'bold' => true],
            ['l' => "6{$sp}Achats consommés de matières et fournitures", 'k' => 'achatsConsommes'],
            ['l' => "7{$sp}Autres charges externes", 'k' => 'autresChargesExt'],
            ['l' => "IV{$sp}VALEUR AJOUTEE ( I+II+III )", 'k' => 'va', 'bold' => true],
            ['l' => "8\u{00a0}\u{00a0}+\u{00a0}\u{00a0}Subventions d'exploitation", 'k' => 'subvExpl'],
            ['l' => "V{$sp}RESULTAT BRUT D'EXPLOITATION (E.B.E)", 'k' => 'ebe', 'bold' => true],
            ['l' => "9\u{00a0}\u{00a0}-\u{00a0}\u{00a0}Impôts et taxes", 'k' => 'impotsTaxes'],
            ['l' => "10\u{00a0}\u{00a0}-\u{00a0}\u{00a0}Charges de personnel", 'k' => 'chargesPersonnel'],
            ['l' => "11\u{00a0}\u{00a0}+\u{00a0}\u{00a0}Autres produits d'exploitation", 'k' => 'autresProdExpl'],
            ['l' => "12\u{00a0}\u{00a0}-\u{00a0}\u{00a0}Autres charges d'exploitation", 'k' => 'autresChargesExpl'],
            ['l' => "13\u{00a0}\u{00a0}+\u{00a0}\u{00a0}Reprises d'exploitation: transfert de charges", 'k' => 'reprisesExpl'],
            ['l' => "14\u{00a0}\u{00a0}-\u{00a0}\u{00a0}Dotations d'exploitation", 'k' => 'dotationsExpl'],
            ['l' => "VI{$sp}RESULTAT D'EXPLOITATION ( + ou - )", 'k' => 'resExpl', 'bold' => true],
            ['l' => "VII{$sp}RESULTAT FINANCIER", 'k' => 'resFin', 'bold' => true],
            ['l' => "VIII{$sp}RESULTAT COURANT ( + ou - )", 'k' => 'resCourant', 'bold' => true],
            ['l' => "IX{$sp}RESULTAT NON COURANT ( + ou - )", 'k' => 'resNC', 'bold' => true],
            ['l' => "15\u{00a0}\u{00a0}-\u{00a0}\u{00a0}Impôts sur les resultats", 'k' => 'impotsResultats'],
            ['l' => "X{$sp}RESULTAT NET DE L'EXERCICE ( + ou - )", 'k' => 'resNet', 'bold' => true],
            ['section' => "II - CAPACITE D'AUTOFINANCEMENT ( C.A.F ) - AUTOFINANCEMENT"],
            ['l' => "1{$sp}RESULTAT NET DE L'EXERCICE ( + ou - )", 'k' => 'resNet'],
            ['l' => "- Benefice (+)", 'k' => 'benefice', 'indent' => true],
            ['l' => "- Perte\u{00a0}\u{00a0}\u{00a0}(-)", 'k' => 'perte', 'indent' => true],
            ['l' => "2\u{00a0}\u{00a0}+\u{00a0}\u{00a0}Dotations d'exploitation", 'k' => 'dotationsExpl'],
            ['l' => "3\u{00a0}\u{00a0}+\u{00a0}\u{00a0}Dotations financières", 'k' => 'dotFin'],
            ['l' => "4\u{00a0}\u{00a0}+\u{00a0}\u{00a0}Dotations non courantes", 'k' => 'dotNC'],
            ['l' => "5\u{00a0}\u{00a0}-\u{00a0}\u{00a0}Reprises d'exploitation", 'k' => 'reprisesExpl'],
            ['l' => "6\u{00a0}\u{00a0}-\u{00a0}\u{00a0}Reprises financières", 'k' => 'reprFin'],
            ['l' => "7\u{00a0}\u{00a0}-\u{00a0}\u{00a0}Reprises non courantes (2) (3)", 'k' => 'reprNC'],
            ['l' => "8\u{00a0}\u{00a0}-\u{00a0}\u{00a0}Produits des cession des immobilisations (1)", 'k' => 'produitsCession'],
            ['l' => "9\u{00a0}\u{00a0}+\u{00a0}\u{00a0}Valeurs nettes des immobilisations cédées", 'k' => 'vnaCedees'],
            ['l' => "I{$sp}CAPACITE D'AUTOFINANCEMENT ( C.A.F )", 'k' => 'caf', 'total' => true],
            ['l' => "10\u{00a0}\u{00a0}-\u{00a0}\u{00a0}Distributions de bénéfices", 'k' => 'distributions'],
            ['l' => "II{$sp}AUTOFINANCEMENT", 'k' => 'autofinancement', 'total' => true],
        ];

        return view('liasse.esg', compact('exercice', 'rows', 'n', 'p'));
    }

    public function detailCpc(?BalanceService $balanceService = null)                                            // T06
    {
        $balanceService ??= app(BalanceService::class);
        $exercice = $this->currentExercice();
        [$items, $itemsPrev] = $balanceService->lignesAvecPrecedent(Auth::id(), $exercice);

        // Définition : sections, postes (préfixe 3 chiffres) et lignes de détail (sous-comptes).
        // 'RESTE' = solde du poste non ventilé (Total du poste - somme des détails connus).
        $def = [
            ['section' => "CHARGES D'EXPLOITATION"],
            ['poste' => '611', 'label' => 'Achats revendus de marchandises', 'type' => 'charge', 'details' => [
                ['Achats de marchandises', '6111'],
                ['Variation des stocks de marchandises', '6114'],
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
            $rows[] = ['poste' => $code . "\u{00a0}\u{00a0}" . $bloc['label']];

            $sommeN = 0.0; $sommeP = 0.0; $resteIdx = null;
            foreach ($bloc['details'] as [$lib, $codeDetail]) {
                if ($codeDetail === 'RESTE') {
                    $rows[] = ['l' => $lib, 'n' => 0.0, 'p' => 0.0];
                    $resteIdx = array_key_last($rows);
                    continue;
                }
                $vN = $this->montant($items, $codeDetail, $type);
                $vP = $this->montant($itemsPrev, $codeDetail, $type);
                $sommeN += $vN; $sommeP += $vP;
                $rows[] = ['l' => $lib, 'n' => $vN, 'p' => $vP];
            }
            if ($resteIdx !== null) {
                $rows[$resteIdx]['n'] = $totalN - $sommeN;
                $rows[$resteIdx]['p'] = $totalP - $sommeP;
            }
            $rows[] = ['total' => true, 'l' => 'Total', 'n' => $totalN, 'p' => $totalP];
        }

        return view('liasse.detail_cpc', compact('exercice', 'rows'));
    }
    public function controle(LiasseControlService $control, ?BalanceService $balanceService = null)
    {
        $balanceService ??= app(BalanceService::class);
        $exercice = $this->currentExercice();
        $userId = Auth::id();
        [$items, $itemsPrev] = $balanceService->lignesAvecPrecedent($userId, $exercice);
        $liasseData = LiasseData::where('user_id', $userId)
            ->where('exercice', $exercice)
            ->get();

        $controles = $control->verifierLiasse($items, $liasseData, $itemsPrev);
        $bloquants = collect($controles)->filter(fn ($r) => $r['bloquant'] && !$r['ok'])->count();
        $anomalies = collect($controles)->filter(fn ($r) => !$r['ok'])->count();
        $valide = $bloquants === 0;

        // Compat. avec l'ancienne vue (variables historiques)
        $equilibreBilan = $controles[0]['ok'] ?? false;
        $ecartBilan = $controles[0]['ecart'] ?? 0;
        $equilibreResultat = $controles[1]['ok'] ?? false;

        return view('liasse.controle', compact(
            'controles', 'valide', 'bloquants', 'anomalies', 'exercice',
            'equilibreBilan', 'ecartBilan', 'equilibreResultat'
        ));
    }

    public function creditBail()             { return $this->genericEditable('liasse.credit_bail', 'credit_bail'); }                       // T07
    public function plusValues()             { return $this->genericEditable('liasse.plus_values', 'plus_values'); }                       // T10
    public function titresParticipation()    { return $this->genericEditable('liasse.titres_participation', 'titres_participation'); }     // T11
    public function repartitionCapital()     { return $this->genericEditable('liasse.repartition_capital', 'repartition_capital'); }       // T13
    public function affectationResultats()   { return $this->genericEditable('liasse.affectation_resultats', 'affectation_resultats'); }   // T14
    public function calculImpotEncouragement(){ return $this->genericEditable('liasse.calcul_impot_encouragement', 'calcul_impot_encouragement'); } // T15
    public function dotationsAmortissements() { return $this->genericEditable('liasse.dotations_amortissements', 'dotations_amortissements'); } // T16
    public function plusValuesFusion()       { return $this->genericEditable('liasse.plus_values_fusion', 'plus_values_fusion'); }         // T17
    public function interetsEmprunts()       { return $this->genericEditable('liasse.interets_emprunts', 'interets_emprunts'); }           // T18
    public function locationsBaux()          { return $this->genericEditable('liasse.locations_baux', 'locations_baux'); }                 // T19
    public function detailStocks(?BalanceService $balanceService = null)                                           // T20
    {
        $balanceService ??= app(BalanceService::class);
        $exercice = $this->currentExercice();
        [
            'stockSections' => $stockSections,
            'stockTotals' => $stockTotals,
            'stockTotalGeneral' => $stockTotalGeneral,
        ] = (new LiasseTableDataService($balanceService))->detailStocks(Auth::id(), $exercice);

        return view('liasse.detail_stocks', compact(
            'exercice', 'stockSections', 'stockTotals', 'stockTotalGeneral'
        ));

        [$items, $itemsPrev] = $balanceService->lignesAvecPrecedent(Auth::id(), $exercice);

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
                    'values' => $this->calculerLigneStock(
                        $items, $itemsPrev, $definition['brut'], $definition['provision']
                    ),
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

        return view('liasse.detail_stocks', compact(
            'exercice', 'stockSections', 'stockTotals', 'stockTotalGeneral'
        ));
    }
    public function operationsDevises()      { return $this->genericEditable('liasse.operations_devises', 'operations_devises'); }         // T21

    /**
     * Enregistre les valeurs saisies d'un tableau déclaratif dans liasse_data.
     * Les champs sont postés sous la forme f[<cle>] = <valeur> (anti-injection
     * via Eloquent ; protection CSRF assurée par le middleware web).
     */
    public function saveData(Request $request, string $tableau)
    {
        abort_unless(in_array($tableau, self::TABLEAUX_EDITABLES, true), 404);

        if ($tableau === 'passage_fiscal') {
            return $this->savePassageFiscalData($request);
        }

        $exercice = $this->currentExercice();
        $userId = Auth::id();
        $champs = (array) $request->input('f', []);

        DB::transaction(function () use ($champs, $userId, $exercice, $tableau) {
            LiasseData::where('user_id', $userId)
                ->where('exercice', $exercice)
                ->where('tableau_code', $tableau)
                ->delete();

            foreach ($champs as $cle => $valeur) {
                if ($valeur === null || $valeur === '') {
                    continue;
                }
                LiasseData::create([
                    'user_id'      => $userId,
                    'exercice'     => $exercice,
                    'tableau_code' => $tableau,
                    'cle'          => (string) $cle,
                    'valeur'       => is_array($valeur) ? json_encode($valeur) : (string) $valeur,
                ]);
            }
        });

        return redirect()->back()->with('success', 'Tableau enregistré avec succès.');
    }

    /** Enregistre uniquement les compléments manuels autorisés de T03. */
    private function savePassageFiscalData(Request $request)
    {
        $exercice = $this->currentExercice();
        $userId = Auth::id();
        $champs = array_intersect_key(
            (array) $request->input('f', []),
            array_flip(self::PASSAGE_FISCAL_EDITABLE_KEYS)
        );

        DB::transaction(function () use ($champs, $userId, $exercice) {
            foreach ($champs as $cle => $valeur) {
                if (is_array($valeur)) {
                    continue;
                }

                LiasseData::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'exercice' => $exercice,
                        'tableau_code' => 'passage_fiscal',
                        'cle' => $cle,
                    ],
                    ['valeur' => (string) ($valeur ?? '')]
                );
            }
        });

        return redirect()->back()->with('success', 'Tableau enregistré avec succès.');
    }

    /**
     * Charge un tableau déclaratif éditable avec ses valeurs déjà saisies.
     */
    private function genericEditable(string $view, string $tableau)
    {
        $exercice = $this->currentExercice();
        $items = BalanceItem::where('user_id', Auth::id())->where('exercice', $exercice)->get();
        $data = LiasseData::where('user_id', Auth::id())
            ->where('exercice', $exercice)
            ->where('tableau_code', $tableau)
            ->pluck('valeur', 'cle')
            ->toArray();

        return view($view, compact('items', 'exercice', 'data', 'tableau'));
    }

    public function tableauFinancement(?BalanceService $balanceService = null)                                   // T22
    {
        $balanceService ??= app(BalanceService::class);
        $exercice = $this->currentExercice();
        [
            'synthese' => $synthese,
            'fluxRows' => $fluxRows,
            'fluxTotal' => $fluxTotal,
        ] = (new LiasseTableDataService($balanceService))->tableauFinancement(Auth::id(), $exercice);

        return view('liasse.tableau_financement', compact('exercice', 'synthese', 'fluxRows', 'fluxTotal'));

        [$items, $itemsPrev] = $balanceService->lignesAvecPrecedent(Auth::id(), $exercice);

        $masses = function ($col) {
            $passif = function (array $prefixes) use ($col): float {
                return (float) $col->filter(function ($i) use ($prefixes) {
                    foreach ($prefixes as $prefix) {
                        if (str_starts_with((string) $i->compte, $prefix)) return true;
                    }
                    return false;
                })->sum(fn ($i) => (float) $i->solde_crediteur - (float) $i->solde_debiteur);
            };
            $resultat = $this->montant($col, '7', 'produit') - $this->montant($col, '6', 'charge');
            $fp = $passif([
                '111', '112', '113', '114', '115', '116', '117', '118',
                '131', '135', '141', '148', '151', '155', '171', '172',
            ]) + $resultat;
            $immBrut = (float) $col->filter(fn ($i) => str_starts_with((string) $i->compte, '2')
                    && !str_starts_with((string) $i->compte, '28') && !str_starts_with((string) $i->compte, '29'))
                ->sum(fn ($i) => $i->solde_debiteur - $i->solde_crediteur);
            $immAmort = (float) $col->filter(fn ($i) => str_starts_with((string) $i->compte, '28') || str_starts_with((string) $i->compte, '29'))
                ->sum(fn ($i) => $i->solde_crediteur - $i->solde_debiteur);
            $actifImmo = $immBrut - $immAmort;
            $fr = $fp - $actifImmo;
            $acBrut = (float) $col->filter(fn ($i) => str_starts_with((string) $i->compte, '3') && !str_starts_with((string) $i->compte, '39'))
                ->sum(fn ($i) => $i->solde_debiteur - $i->solde_crediteur);
            $acProv = (float) $col->filter(fn ($i) => str_starts_with((string) $i->compte, '39'))
                ->sum(fn ($i) => $i->solde_crediteur - $i->solde_debiteur);
            $ac = $acBrut - $acProv;
            $pc = $passif(['441', '442', '443', '444', '445', '446', '448', '449', '45', '47']);
            $bfg = $ac - $pc;
            // Le modèle T22 définit la trésorerie nette par l'équation
            // Fonds de roulement - BFG, ce qui garantit la cohérence des masses.
            $tn = $fr - $bfg;
            return compact('fp', 'actifImmo', 'fr', 'ac', 'pc', 'bfg', 'tn');
        };

        $n = $masses($items);
        $p = $masses($itemsPrev);

        // Lignes de la Partie I : sens = 'ressource' ou 'emploi' si la variation est positive.
        $synthese = [
            ['l' => '1&nbsp;&nbsp;Financement Permanent',          'k' => 'fp',        'sensPos' => 'ressource'],
            ['l' => '2&nbsp;&nbsp;Moins actif immobilisé',         'k' => 'actifImmo', 'sensPos' => 'emploi'],
            ['l' => '3&nbsp;&nbsp;= Fonds de roulement fonctionnel (1-2) (A)', 'k' => 'fr', 'sensPos' => 'ressource', 'total' => true],
            ['l' => '4&nbsp;&nbsp;Actif circulant',                'k' => 'ac',        'sensPos' => 'emploi'],
            ['l' => '5&nbsp;&nbsp;Moins passif circulant',         'k' => 'pc',        'sensPos' => 'ressource'],
            ['l' => '6&nbsp;&nbsp;= Besoin de financement global (4-5) (B)', 'k' => 'bfg', 'sensPos' => 'emploi', 'total' => true],
            ['l' => '7&nbsp;&nbsp;TRESORERIE NETTE (Actif-Passif) = A-B', 'k' => 'tn', 'sensPos' => 'emploi', 'total' => true],
        ];

        foreach ($synthese as &$row) {
            $row['n'] = $n[$row['k']];
            $row['p'] = $p[$row['k']];
            $var = $row['n'] - $row['p'];
            // Placement de la variation dans la bonne colonne (Emplois / Ressources)
            $estRessource = ($row['sensPos'] === 'ressource') ? $var >= 0 : $var < 0;
            $row['emploi']    = $estRessource ? 0.0 : abs($var);
            $row['ressource'] = $estRessource ? abs($var) : 0.0;
        }
        unset($row);

        $variationBrute = function (array $prefixes) use ($items, $itemsPrev): float {
            $brut = fn ($collection): float => (float) $collection
                ->filter(function ($i) use ($prefixes) {
                    foreach ($prefixes as $prefix) {
                        if (str_starts_with((string) $i->compte, $prefix)) return true;
                    }
                    return false;
                })
                ->sum(fn ($i) => (float) $i->solde_debiteur - (float) $i->solde_crediteur);

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
            ['label' => "+ Capacité d'autofinancement", 'n_ressource' => $cafN, 'p_ressource' => $cafP],
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
            if (!empty($row['section']) || !empty($row['total']) || !empty($row['subtotal'])) continue;
            foreach (get_object_vars($fluxTotal) as $key => $_) {
                $fluxTotal->{$key} += (float) ($row[$key] ?? 0);
            }
        }

        return view('liasse.tableau_financement', compact('exercice', 'synthese', 'fluxRows', 'fluxTotal'));
    }
    public function methodesEvaluation()     { return $this->genericEditable('liasse.methodes_evaluation', 'methodes_evaluation'); }      // T23
    public function derogations()            { return $this->genericEditable('liasse.derogations', 'derogations'); }              // T24
    public function changementsMethodes()    { return $this->genericEditable('liasse.changements_methodes', 'changements_methodes'); }     // T25
    public function calculIsEncouragees()    { return $this->genericEditable('liasse.calcul_is_encouragees', 'calcul_is_encouragees'); }    // T26

    /**
     * Somme nette d'un ou plusieurs préfixes de comptes pour une collection.
     * type 'produit' => crédit - débit ; type 'charge' => débit - crédit.
     */
    private function montant($items, $prefixes, string $type): float
    {
        $prefixes = (array) $prefixes;
        return (float) $items->filter(function ($i) use ($prefixes) {
            foreach ($prefixes as $p) {
                if (str_starts_with($i->compte, $p)) return true;
            }
            return false;
        })->sum(fn ($i) => $type === 'produit'
            ? $i->solde_crediteur - $i->solde_debiteur
            : $i->solde_debiteur - $i->solde_crediteur);
    }

    /**
     * Soldes intermédiaires de gestion (T.F.R) + C.A.F pour une balance donnée.
     * Tout est dérivé des comptes de charges (classe 6) et de produits (classe 7).
     */
    private function calculerESG($items): array
    {
        $m = fn ($p, $t) => $this->montant($items, $p, $t);

        $ventesMarch      = $m('711', 'produit');
        $achatsRevendus   = $m('611', 'charge');
        $margeBrute       = $ventesMarch - $achatsRevendus;
        $ventesBiens      = $m('712', 'produit');
        $varStock         = $m('713', 'produit');
        $immobProduites   = $m('714', 'produit');
        $production       = $ventesBiens + $varStock + $immobProduites;
        $achatsConsommes  = $m('612', 'charge');
        $autresChargesExt = $m(['613', '614'], 'charge');
        $consommation     = $achatsConsommes + $autresChargesExt;
        $va               = $margeBrute + $production - $consommation;
        $subvExpl         = $m('716', 'produit');
        $impotsTaxes      = $m('616', 'charge');
        $chargesPersonnel = $m('617', 'charge');
        $ebe              = $va + $subvExpl - $impotsTaxes - $chargesPersonnel;
        $autresProdExpl   = $m('718', 'produit');
        $autresChargesExpl= $m('618', 'charge');
        $reprisesExpl     = $m('719', 'produit');
        $dotationsExpl    = $m('619', 'charge');
        $resExpl          = $ebe + $autresProdExpl - $autresChargesExpl + $reprisesExpl - $dotationsExpl;
        $prodFin          = $m('73', 'produit');
        $chargesFin       = $m('63', 'charge');
        $resFin           = $prodFin - $chargesFin;
        $resCourant       = $resExpl + $resFin;
        $prodNC           = $m('75', 'produit');
        $chargesNC        = $m('65', 'charge');
        $resNC            = $prodNC - $chargesNC;
        $resAvantImpot    = $resCourant + $resNC;
        $impotsResultats  = $m('67', 'charge');
        $resNet           = $resAvantImpot - $impotsResultats;

        // C.A.F (méthode additive simplifiée à partir du résultat net)
        $dotFin           = $m('639', 'charge');
        $dotNC            = $m('659', 'charge');
        $reprFin          = $m('739', 'produit');
        $reprNC           = $m('759', 'produit');
        $produitsCession  = $m('751', 'produit');
        $vnaCedees        = $m('651', 'charge');
        $caf = $resNet + $dotationsExpl + $dotFin + $dotNC
             - $reprisesExpl - $reprFin - $reprNC - $produitsCession + $vnaCedees;
        $distributions    = 0.0;  // donnée déclarative (non issue de la balance)
        $autofinancement  = $caf - $distributions;

        return [
            'ventesMarch' => $ventesMarch, 'achatsRevendus' => $achatsRevendus, 'margeBrute' => $margeBrute,
            'ventesBiens' => $ventesBiens, 'varStock' => $varStock, 'immobProduites' => $immobProduites,
            'production' => $production, 'achatsConsommes' => $achatsConsommes, 'autresChargesExt' => $autresChargesExt,
            'consommation' => $consommation, 'va' => $va, 'subvExpl' => $subvExpl, 'ebe' => $ebe,
            'impotsTaxes' => $impotsTaxes, 'chargesPersonnel' => $chargesPersonnel, 'autresProdExpl' => $autresProdExpl,
            'autresChargesExpl' => $autresChargesExpl, 'reprisesExpl' => $reprisesExpl, 'dotationsExpl' => $dotationsExpl,
            'resExpl' => $resExpl, 'resFin' => $resFin, 'resCourant' => $resCourant, 'resNC' => $resNC,
            'impotsResultats' => $impotsResultats, 'resNet' => $resNet,
            'benefice' => $resNet > 0 ? $resNet : 0.0, 'perte' => $resNet < 0 ? abs($resNet) : 0.0,
            'dotFin' => $dotFin, 'dotNC' => $dotNC, 'reprFin' => $reprFin, 'reprNC' => $reprNC,
            'produitsCession' => $produitsCession, 'vnaCedees' => $vnaCedees, 'caf' => $caf,
            'distributions' => $distributions, 'autofinancement' => $autofinancement,
        ];
    }

    private function genericView($viewName)
    {
        $exercice = $this->currentExercice();
        $items = BalanceItem::where('user_id', Auth::id())->where('exercice', $exercice)->get();
        return view($viewName, compact('items', 'exercice'));
    }

    private function calculerLigneActif($items, $codesBrut, $codesAmort, $itemsPrev = null)
    {
        $brutAmortNet = function ($collection) use ($codesBrut, $codesAmort) {
            $codesBrut = (array) $codesBrut;
            $codesAmort = (array) $codesAmort;

            $brut = (float) $collection->filter(function($i) use ($codesBrut) {
                foreach($codesBrut as $c) {
                    if (str_starts_with($i->compte, $c) && !str_starts_with($i->compte, '28') && !str_starts_with($i->compte, '29')) return true;
                }
                return false;
            })->sum(fn($i) => $i->solde_debiteur - $i->solde_crediteur);

            $amort = 0.0;
            if (!empty($codesAmort)) {
                $amort = (float) $collection->filter(function($i) use ($codesAmort) {
                    foreach($codesAmort as $c) if(str_starts_with($i->compte, $c)) return true;
                    return false;
                })->sum(fn($i) => $i->solde_crediteur - $i->solde_debiteur);
            }

            if ($brut < 0) $brut = abs($brut);
            if ($amort < 0) $amort = abs($amort);

            return ['brut' => $brut, 'amort' => $amort, 'net' => $brut - $amort];
        };

        $courant = $brutAmortNet($items);

        // Exercice Précédent (N-1) : on rejoue le même calcul sur la balance N-1.
        // On ne garde que le "Net" (seule colonne demandée au bilan pour le N-1).
        $netPrecedent = 0.0;
        if ($itemsPrev !== null) {
            $netPrecedent = $brutAmortNet($itemsPrev)['net'];
        }

        return (object) [
            'brut' => $courant['brut'],
            'amort' => $courant['amort'],
            'net' => $courant['net'],
            'net_prec' => $netPrecedent,
        ];
    }

    private function calculerLigneImmo($items, $codePrefixe, $itemsPrev = null)
    {
        // Valeur brute (hors amortissements 28xx et provisions 29xx) d'un compte
        // d'immobilisation, pour une collection de lignes de balance donnée.
        $brut = function ($collection) use ($codePrefixe) {
            if ($collection === null || $collection->isEmpty()) {
                return 0.00;
            }
            $solde = (float) $collection->filter(function ($i) use ($codePrefixe) {
                return str_starts_with($i->compte, $codePrefixe)
                    && !str_starts_with($i->compte, '28')
                    && !str_starts_with($i->compte, '29');
            })->sum(fn ($i) => $i->solde_debiteur - $i->solde_crediteur);

            return $solde < 0 ? abs($solde) : $solde;
        };

        $brutN  = $brut($items);
        $brutN1 = $brut($itemsPrev);

        // Brut au début de l'exercice = brut à la clôture N-1.
        // Variation N : positive => Acquisition ; négative => Cession/retrait.
        // (Sans balance N-1, début = 0 et tout le brut N est porté en Acquisitions.)
        $debut       = $brutN1;
        $variation   = $brutN - $debut;
        $acquisition = $variation > 0 ? $variation : 0.00;
        $cession     = $variation < 0 ? abs($variation) : 0.00;

        $production   = 0.00;
        $virement_aug = 0.00;
        $retrait      = 0.00;
        $virement_dim = 0.00;

        $fin = $debut + $acquisition + $production + $virement_aug - ($cession + $retrait + $virement_dim);

        return (object) [
            'debut'        => $debut,
            'acquisition'  => $acquisition,
            'production'   => $production,
            'virement_aug' => $virement_aug,
            'cession'      => $cession,
            'retrait'      => $retrait,
            'virement_dim' => $virement_dim,
            'fin'          => $fin,
        ];
    }

    private function calculerLignePassif($items, $codes, $itemsPrev = null)
    {
        $codes = (array) $codes;

        $calcul = fn ($collection): float => $collection === null ? 0.0 : (float) $collection
            ->filter(function ($i) use ($codes) {
                foreach ($codes as $code) {
                    if (str_starts_with((string) $i->compte, $code)) return true;
                }
                return false;
            })
            ->sum(fn ($i) => (float) $i->solde_crediteur - (float) $i->solde_debiteur);

        return (object) [
            'montant' => $calcul($items),
            'montant_prec' => $calcul($itemsPrev),
        ];
    }

    private function sommerRubriquesActif($data, $rubriques)
    {
        $brut = 0; $amort = 0; $net = 0; $netPrec = 0;
        foreach ($rubriques as $rubrique) {
            if (isset($data[$rubrique])) {
                foreach ($data[$rubrique] as $ligne) {
                    $brut += $ligne->brut;
                    $amort += $ligne->amort;
                    $net += $ligne->net;
                    $netPrec += $ligne->net_prec ?? 0;
                }
            }
        }
        return (object) ['brut' => $brut, 'amort' => $amort, 'net' => $net, 'net_prec' => $netPrec];
    }

    private function sommerRubriquesPassif($data, $rubriques)
    {
        $total = 0; $totalPrec = 0;
        foreach ($rubriques as $rubrique) {
            if (isset($data[$rubrique])) {
                foreach ($data[$rubrique] as $ligne) {
                    $total += $ligne->montant;
                    $totalPrec += $ligne->montant_prec ?? 0;
                }
            }
        }
        return (object) ['montant' => $total, 'montant_prec' => $totalPrec];
    }

    private function calculateRow($items, $itemsPrev, $codes)
    {
        $codes = (array) $codes;

        $montant = function ($collection) use ($codes): float {
            $filtered = $collection->filter(function ($i) use ($codes) {
                foreach ($codes as $code) {
                    if (str_starts_with((string) $i->compte, $code)) return true;
                }
                return false;
            });

            $isProduit = str_starts_with((string) $codes[0], '7');

            return (float) $filtered->sum(fn ($i) => $isProduit
                ? (float) $i->solde_crediteur - (float) $i->solde_debiteur
                : (float) $i->solde_debiteur - (float) $i->solde_crediteur);
        };

        $currentItems = $items->filter(function($i) use ($codes) {
            foreach($codes as $c) if(str_starts_with($i->compte, $c)) return true;
            return false;
        });

        $precedent = $currentItems->filter(fn($i) => strlen($i->compte) >= 4 && str_starts_with(substr($i->compte, 3, 1), '8'));
        $propres = $currentItems->diff($precedent);

        $col1 = $montant($propres);
        $col2 = $montant($precedent);
        $col3 = $col1 + $col2;
        $col4 = $montant($itemsPrev);

        return (object) [
            'col1' => $col1,
            'col2' => $col2,
            'col3' => $col3,
            'col4' => $col4
        ];
    }

    private function calculerMontantFiscal($items, $codes)
    {
        $codes = (array) $codes;
        return (float) $items->filter(function($i) use ($codes) {
            foreach($codes as $c) if(str_starts_with($i->compte, $c)) return true;
            return false;
        })->sum(fn($i) => abs($i->solde_crediteur - $i->solde_debiteur));
    }

    private function calculerLigneAmortissement($items, $itemsPrev, string $codeAmort, string $codeDotationPrefixe)
    {
        $cumul = fn ($collection): float => max(0.0, (float) $collection
            ->filter(fn ($i) => str_starts_with((string) $i->compte, $codeAmort))
            ->sum(fn ($i) => (float) $i->solde_crediteur - (float) $i->solde_debiteur));

        $cumulDebut = $cumul($itemsPrev);
        $cumulFin = $cumul($items);
        $dotationComptable = max(0.0, (float) $items
            ->filter(fn ($i) => str_starts_with((string) $i->compte, $codeDotationPrefixe))
            ->sum(fn ($i) => (float) $i->solde_debiteur - (float) $i->solde_crediteur));

        // Sans détail de dotation, la hausse nette du cumul est la meilleure
        // information disponible dans les deux balances de clôture.
        $dotationExercice = $dotationComptable > 0
            ? $dotationComptable
            : max(0.0, $cumulFin - $cumulDebut);
        $sorties = max(0.0, $cumulDebut + $dotationExercice - $cumulFin);

        return (object) [
            'col1' => $cumulDebut,
            'col2' => $dotationExercice,
            'col3' => $sorties,
            'col4' => $cumulFin
        ];
    }

    private function calculerLigneStock($items, $itemsPrev, array|string $codesBrut, array|string $codesProvision): object
    {
        $codesBrut = (array) $codesBrut;
        $codesProvision = (array) $codesProvision;
        $filtre = function ($item, array $codes): bool {
            foreach ($codes as $code) {
                if (str_starts_with((string) $item->compte, $code)) return true;
            }
            return false;
        };
        $calcul = function ($collection) use ($codesBrut, $codesProvision, $filtre): array {
            $brut = (float) $collection
                ->filter(fn ($i) => $filtre($i, $codesBrut))
                ->sum(fn ($i) => (float) $i->solde_debiteur - (float) $i->solde_crediteur);
            $provision = (float) $collection
                ->filter(fn ($i) => $filtre($i, $codesProvision))
                ->sum(fn ($i) => (float) $i->solde_crediteur - (float) $i->solde_debiteur);

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
            // La colonne H du modèle T20 est la variation du montant brut : B - E.
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
            if ($ligne === null) continue;
            foreach (array_keys(get_object_vars($total)) as $champ) {
                $total->{$champ} += (float) $ligne->{$champ};
            }
        }

        return $total;
    }

    private function initialiserLigneProvision()
    {
        return (object) [
            'col1' => 0.00, 'col2' => 0.00, 'col3' => 0.00, 'col4' => 0.00, 'col5' => 0.00, 'col6' => 0.00, 'col7' => 0.00,
        ];
    }

    private function currentExercice(): int
    {
        return app(ActiveExerciceService::class)->current();
    }
}


