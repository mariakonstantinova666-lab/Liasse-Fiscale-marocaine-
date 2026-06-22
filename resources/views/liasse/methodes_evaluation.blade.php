@extends('layouts.app')

@section('content')
<div class="bg-white shadow-lg rounded-sm border border-slate-200 p-6">
    <form method="POST" action="{{ route('liasse.save', 'methodes_evaluation') }}">
    @csrf
    <div class="flex justify-between items-center mb-4 border-b pb-2">
        <div>
            <h2 class="text-xl font-bold text-slate-800 uppercase tracking-wider">
                Principales méthodes d'évaluation spécifiques à l'entreprise
            </h2>
            <p class="text-sm text-slate-500 mt-1">Exercice : <strong>{{ $exercice ?? session('annee_exercice', 2025) }}</strong></p>
        </div>
        <span class="text-sm font-semibold bg-slate-100 px-3 py-1 rounded text-slate-600">Tableau N° 23 — Exercice {{ $exercice ?? session('annee_exercice', 2025) }}</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-xs text-left border-collapse border border-slate-300" style="min-width: 900px;">
            <thead class="text-white text-center font-bold">
                <tr>
                    <th class="p-2 border border-slate-300 bg-slate-800 text-left" style="width: 40%;">Indications</th>
                    <th class="p-2 border border-slate-300 bg-slate-800 text-left">Méthodes / Justifications</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $sections = [
                        'I. ACTIF IMMOBILISE' => [
                            'A. EVALUATION A L\'ENTREE',
                            '1. Immobilisation en non-valeurs',
                            '2. Immobilisation incorporelles',
                            '3. Immobilisations corporelles',
                            '4. Immobilisations financières',
                            'B. CORRECTIONS DE VALEUR',
                            '1. Méthodes d\'amortissements',
                            '2. Méthodes d\'évaluation des provisions pour dépréciation',
                            '3. Méthodes de détermination des écarts de conversion - Actif',
                        ],
                        'II. ACTIF CIRCULANT (Hors trésorerie)' => [
                            'A. EVALUATION A L\'ENTREE',
                            '1. Stocks',
                            '2. Créances',
                            '3. Titres et valeurs de placement',
                            'B. CORRECTIONS DE VALEUR',
                            '1. Méthodes d\'évaluation des provisions pour dépréciation',
                            '2. Méthodes de détermination des écarts de conversion - Actif',
                        ],
                        'III. FINANCEMENT PERMANENT' => [
                            '1. Méthodes de réévaluation',
                            '2. Méthodes d\'évaluation des provisions réglementées',
                            '3. Dettes de financement permanent',
                            '4. Méthodes d\'évaluation des provisions durables pour risques et charges',
                            '5. Méthodes de détermination des écarts de conversion - Passif',
                        ],
                        'IV. PASSIF CIRCULANT (Hors trésorerie)' => [
                            '1. Dettes du passif circulant',
                            '2. Méthodes d\'évaluation des autres provisions pour risques et charges',
                            '3. Méthodes de détermination des écarts de conversion - Passif',
                        ],
                        'V. TRESORERIE' => [
                            '1. Trésorerie - Actif',
                            '2. Trésorerie - Passif',
                            '3. Méthodes d\'évaluation des provisions pour dépréciation',
                        ],
                    ];
                @endphp

                @foreach($sections as $titre => $lignes)
                    @php $si = $loop->index; @endphp
                    <tr class="bg-slate-200 font-bold text-slate-900 border-t border-slate-300">
                        <td colspan="2" class="p-2 border border-slate-300 uppercase tracking-wide">{{ $titre }}</td>
                    </tr>
                    @foreach($lignes as $ligne)
                        @php $cle = 'methode_'.$si.'_'.$loop->index; @endphp
                        <tr class="hover:bg-slate-50 border-b border-slate-200">
                            <td class="p-2 border border-slate-200 font-medium text-slate-600">{{ $ligne }}</td>
                            <td class="p-1 border border-slate-200"><textarea name="f[{{ $cle }}]" rows="2" class="w-full bg-transparent px-1 py-1 focus:bg-yellow-50 outline-none rounded resize-y">{{ $data[$cle] ?? '' }}</textarea></td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4 flex justify-end"><button type="submit" class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">💾 Enregistrer le tableau</button></div>
    </form>
</div>
@endsection
