<?php

use App\Http\Controllers\BalanceController;
use App\Http\Controllers\LiasseController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SocieteController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 1. PAGE D'ACCUEIL
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});

// 2. ESPACE SÉCURISÉ (Authentifié et Vérifié)
Route::middleware(['auth', 'verified'])->group(function () {
    
    // --- TABLEAU DE BORD PRINCIPAL (Inertia / Vue.js) ---
    Route::get('/dashboard', [BalanceController::class, 'index'])->name('dashboard');
    Route::post('/balance/import', [BalanceController::class, 'import'])->name('balance.import');

    // --- MA SOCIÉTÉ (création / mise à jour) ---
    Route::post('/societe', [SocieteController::class, 'save'])->name('societe.save');

    // --- ACCÈS AUX TABLEAUX BLADE (LiasseController) ---
    Route::prefix('liasse')->name('liasse.')->group(function () {
        Route::get('/bilan-actif', [LiasseController::class, 'bilanActif'])->name('bilan_actif');
        Route::get('/bilan-passif', [LiasseController::class, 'bilanPassif'])->name('bilan_passif');
        Route::get('/cpc', [LiasseController::class, 'cpc'])->name('cpc');
        Route::get('/passage-fiscal', [LiasseController::class, 'passageFiscal'])->name('passage_fiscal');
        Route::get('/amortissements', [LiasseController::class, 'amortissements'])->name('amortissements');
        Route::get('/immobilisations', [LiasseController::class, 'immobilisations'])->name('immobilisations'); // <-- Nouvelle route ajoutée ici
        Route::get('/provisions', [LiasseController::class, 'provisions'])->name('provisions');
        Route::get('/tva', [LiasseController::class, 'tva'])->name('tva');
        Route::get('/controle', [LiasseController::class, 'controle'])->name('controle');

        // --- Tableaux supplémentaires (T05 → T26) ---
        Route::get('/esg', [LiasseController::class, 'esg'])->name('esg');
        Route::get('/detail-cpc', [LiasseController::class, 'detailCpc'])->name('detail_cpc');
        Route::get('/credit-bail', [LiasseController::class, 'creditBail'])->name('credit_bail');
        Route::get('/plus-values', [LiasseController::class, 'plusValues'])->name('plus_values');
        Route::get('/titres-participation', [LiasseController::class, 'titresParticipation'])->name('titres_participation');
        Route::get('/repartition-capital', [LiasseController::class, 'repartitionCapital'])->name('repartition_capital');
        Route::get('/affectation-resultats', [LiasseController::class, 'affectationResultats'])->name('affectation_resultats');
        Route::get('/calcul-impot-encouragement', [LiasseController::class, 'calculImpotEncouragement'])->name('calcul_impot_encouragement');
        Route::get('/dotations-amortissements', [LiasseController::class, 'dotationsAmortissements'])->name('dotations_amortissements');
        Route::get('/plus-values-fusion', [LiasseController::class, 'plusValuesFusion'])->name('plus_values_fusion');
        Route::get('/interets-emprunts', [LiasseController::class, 'interetsEmprunts'])->name('interets_emprunts');
        Route::get('/locations-baux', [LiasseController::class, 'locationsBaux'])->name('locations_baux');
        Route::get('/detail-stocks', [LiasseController::class, 'detailStocks'])->name('detail_stocks');
        Route::get('/operations-devises', [LiasseController::class, 'operationsDevises'])->name('operations_devises');
        Route::get('/tableau-financement', [LiasseController::class, 'tableauFinancement'])->name('tableau_financement');
        Route::get('/methodes-evaluation', [LiasseController::class, 'methodesEvaluation'])->name('methodes_evaluation');
        Route::get('/derogations', [LiasseController::class, 'derogations'])->name('derogations');
        Route::get('/changements-methodes', [LiasseController::class, 'changementsMethodes'])->name('changements_methodes');
        Route::get('/calcul-is-encouragees', [LiasseController::class, 'calculIsEncouragees'])->name('calcul_is_encouragees');

        // --- Sauvegarde des tableaux déclaratifs (saisie manuelle) ---
        Route::post('/{tableau}/save', [LiasseController::class, 'saveData'])->name('save');
    });

    // --- PROFIL UTILISATEUR ---
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';