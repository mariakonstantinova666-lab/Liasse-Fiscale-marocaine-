<?php

use App\Http\Controllers\BalanceController;
use App\Http\Controllers\LiasseController;
use App\Http\Controllers\ProfileController;
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
    });

    // --- PROFIL UTILISATEUR ---
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';