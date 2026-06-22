@extends('layouts.app')

@section('content')
<div class="bg-white shadow-lg rounded-sm border border-slate-200 p-6">
    <form method="POST" action="{{ route('liasse.save', 'affectation_resultats') }}">
        @csrf
    <div class="flex justify-between items-center mb-4 border-b pb-2">
        <div>
            <h2 class="text-xl font-bold text-slate-800 uppercase tracking-wider">
                ÉTAT D'AFFECTATION DES RÉSULTATS INTERVENUE AU COURS DE L'EXERCICE
            </h2>
            <p class="text-sm text-slate-500 mt-1">Exercice : <strong>{{ $exercice ?? session('annee_exercice', 2025) }}</strong></p>
        </div>
        <span class="text-sm font-semibold bg-slate-100 px-3 py-1 rounded text-slate-600">Tableau N° 14 — Exercice {{ $exercice ?? session('annee_exercice', 2025) }}</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-xs text-left border-collapse border border-slate-300" style="min-width: 900px;">
            <thead class="text-white text-center font-bold">
                <tr>
                    <th class="p-2 border border-slate-300 bg-slate-800" style="width: 35%;">Eléments</th>
                    <th class="p-2 border border-slate-300 bg-slate-800" style="width: 15%;">Montant</th>
                    <th class="p-2 border border-slate-300 bg-slate-800" style="width: 35%;">Eléments</th>
                    <th class="p-2 border border-slate-300 bg-slate-800" style="width: 15%;">Montant</th>
                </tr>
            </thead>
            <tbody>
                <tr class="bg-slate-200 font-bold text-slate-900 uppercase border-t border-slate-300">
                    <td colspan="2" class="p-2 border border-slate-300 tracking-wide">A. ORIGINE DES RESULTATS A AFFECTER</td>
                    <td colspan="2" class="p-2 border border-slate-300 tracking-wide">B. AFFECTATION DES RESULTATS</td>
                </tr>

                <tr class="hover:bg-slate-50 border-b border-slate-200">
                    <td class="p-2 border border-slate-200 pl-4 font-medium text-slate-600">(Décision du __/__/____)</td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne1_montantA]" value="{{ $data['ligne1_montantA'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-2 border border-slate-200 pl-4 font-medium text-slate-600">- Réserve légale</td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne1_montantB]" value="{{ $data['ligne1_montantB'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                </tr>
                <tr class="hover:bg-slate-50 border-b border-slate-200">
                    <td class="p-2 border border-slate-200 pl-4 font-medium text-slate-600">- Report à nouveau</td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne2_montantA]" value="{{ $data['ligne2_montantA'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-2 border border-slate-200 pl-4 font-medium text-slate-600">- Autres réserves</td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne2_montantB]" value="{{ $data['ligne2_montantB'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                </tr>
                <tr class="hover:bg-slate-50 border-b border-slate-200">
                    <td class="p-2 border border-slate-200 pl-4 font-medium text-slate-600">- Résultats nets en instance d'affectation</td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne3_montantA]" value="{{ $data['ligne3_montantA'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-2 border border-slate-200 pl-4 font-medium text-slate-600">- Tantièmes</td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne3_montantB]" value="{{ $data['ligne3_montantB'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                </tr>
                <tr class="hover:bg-slate-50 border-b border-slate-200">
                    <td class="p-2 border border-slate-200 pl-4 font-medium text-slate-600">- Résultat net de l'exercice</td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne4_montantA]" value="{{ $data['ligne4_montantA'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-2 border border-slate-200 pl-4 font-medium text-slate-600">- Dividendes</td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne4_montantB]" value="{{ $data['ligne4_montantB'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                </tr>
                <tr class="hover:bg-slate-50 border-b border-slate-200">
                    <td class="p-2 border border-slate-200 pl-4 font-medium text-slate-600">- Prélèvements sur les réserves</td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne5_montantA]" value="{{ $data['ligne5_montantA'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-2 border border-slate-200 pl-4 font-medium text-slate-600">- Autres affectations</td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne5_montantB]" value="{{ $data['ligne5_montantB'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                </tr>
                <tr class="hover:bg-slate-50 border-b border-slate-200">
                    <td class="p-2 border border-slate-200 pl-4 font-medium text-slate-600">- Autres prélèvements</td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne6_montantA]" value="{{ $data['ligne6_montantA'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-2 border border-slate-200 pl-4 font-medium text-slate-600">- Report à nouveau</td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne6_montantB]" value="{{ $data['ligne6_montantB'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                </tr>

                <tr class="bg-slate-100 font-bold text-slate-900 border-y border-slate-400">
                    <td class="p-2 border border-slate-300 uppercase">Total A</td>
                    <td class="p-1 border border-slate-300"><input type="text" name="f[total_A]" value="{{ $data['total_A'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-2 border border-slate-300 uppercase">Total B</td>
                    <td class="p-1 border border-slate-300"><input type="text" name="f[total_B]" value="{{ $data['total_B'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                </tr>
                <tr class="bg-slate-200 font-bold text-slate-900 border-t border-slate-300">
                    <td colspan="4" class="p-2 border border-slate-300 text-center uppercase tracking-wide">TOTAL A = TOTAL B</td>
                </tr>
            </tbody>
        </table>
    </div>
        <div class="mt-4 flex justify-end"><button type="submit" class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">💾 Enregistrer le tableau</button></div>
    </form>
</div>
@endsection
