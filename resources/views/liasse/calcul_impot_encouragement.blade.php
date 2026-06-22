@extends('layouts.app')

@section('content')
<div class="bg-white shadow-lg rounded-sm border border-slate-200 p-6">
    <form method="POST" action="{{ route('liasse.save', 'calcul_impot_encouragement') }}">
        @csrf
    <div class="flex justify-between items-center mb-4 border-b pb-2">
        <div>
            <h2 class="text-xl font-bold text-slate-800 uppercase tracking-wider">
                ÉTAT POUR LE CALCUL DE L'IMPÔT DÛ PAR LES ENTREPRISES BÉNÉFICIANT DES MESURES D'ENCOURAGEMENT AUX INVESTISSEMENTS
            </h2>
            <p class="text-sm text-slate-500 mt-1">Exercice : <strong>{{ $exercice ?? session('annee_exercice', 2025) }}</strong></p>
        </div>
        <span class="text-sm font-semibold bg-slate-100 px-3 py-1 rounded text-slate-600">Tableau N° 15 — Exercice {{ $exercice ?? session('annee_exercice', 2025) }}</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-xs text-left border-collapse border border-slate-300" style="min-width: 1100px;">
            <thead class="text-white text-center font-bold">
                <tr>
                    <th class="p-2 border border-slate-300 bg-slate-800 align-middle" style="width: 40%;">Rubriques</th>
                    <th class="p-2 border border-slate-300 bg-slate-800 align-middle">Ensemble des produits</th>
                    <th class="p-2 border border-slate-300 bg-slate-800 align-middle">Ensemble des produits correspondant à la base imposable</th>
                    <th class="p-2 border border-slate-300 bg-slate-800 align-middle">Ensemble des produits correspondant au numérateur taxable</th>
                </tr>
                <tr>
                    <th class="p-1 border border-slate-300 bg-slate-700 text-center">1</th>
                    <th class="p-1 border border-slate-300 bg-slate-700 text-center">2</th>
                    <th class="p-1 border border-slate-300 bg-slate-700 text-center">3</th>
                    <th class="p-1 border border-slate-300 bg-slate-700 text-center">4</th>
                </tr>
            </thead>
            <tbody>
                <tr class="bg-slate-200 font-bold text-slate-900 uppercase border-t border-slate-300">
                    <td colspan="4" class="p-2 border border-slate-300 tracking-wide">Ventes</td>
                </tr>
                <tr class="hover:bg-slate-50 border-b border-slate-200">
                    <td class="p-2 border border-slate-200 pl-4 font-medium text-slate-600">1&nbsp;&nbsp;Ventes imposables</td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne1_c2]" value="{{ $data['ligne1_c2'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne1_c3]" value="{{ $data['ligne1_c3'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne1_c4]" value="{{ $data['ligne1_c4'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                </tr>
                <tr class="hover:bg-slate-50 border-b border-slate-200">
                    <td class="p-2 border border-slate-200 pl-4 font-medium text-slate-600">2&nbsp;&nbsp;Ventes exonérées à 100 %</td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne2_c2]" value="{{ $data['ligne2_c2'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne2_c3]" value="{{ $data['ligne2_c3'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne2_c4]" value="{{ $data['ligne2_c4'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                </tr>
                <tr class="hover:bg-slate-50 border-b border-slate-200">
                    <td class="p-2 border border-slate-200 pl-4 font-medium text-slate-600">3&nbsp;&nbsp;Ventes à l'export (Imposition à 17,5%)</td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne3_c2]" value="{{ $data['ligne3_c2'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne3_c3]" value="{{ $data['ligne3_c3'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne3_c4]" value="{{ $data['ligne3_c4'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                </tr>

                <tr class="bg-slate-200 font-bold text-slate-900 uppercase border-t border-slate-300">
                    <td colspan="4" class="p-2 border border-slate-300 tracking-wide">Lotissement et promotion immobilière</td>
                </tr>
                <tr class="hover:bg-slate-50 border-b border-slate-200">
                    <td class="p-2 border border-slate-200 pl-4 font-medium text-slate-600">4&nbsp;&nbsp;Ventes et locations imposables</td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne4_c2]" value="{{ $data['ligne4_c2'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne4_c3]" value="{{ $data['ligne4_c3'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne4_c4]" value="{{ $data['ligne4_c4'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                </tr>
                <tr class="hover:bg-slate-50 border-b border-slate-200">
                    <td class="p-2 border border-slate-200 pl-4 font-medium text-slate-600">5&nbsp;&nbsp;Ventes et locations exclues à 100 %</td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne5_c2]" value="{{ $data['ligne5_c2'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne5_c3]" value="{{ $data['ligne5_c3'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne5_c4]" value="{{ $data['ligne5_c4'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                </tr>
                <tr class="hover:bg-slate-50 border-b border-slate-200">
                    <td class="p-2 border border-slate-200 pl-4 font-medium text-slate-600">6&nbsp;&nbsp;Ventes à l'export</td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne6_c2]" value="{{ $data['ligne6_c2'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne6_c3]" value="{{ $data['ligne6_c3'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne6_c4]" value="{{ $data['ligne6_c4'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                </tr>

                <tr class="bg-slate-200 font-bold text-slate-900 uppercase border-t border-slate-300">
                    <td colspan="4" class="p-2 border border-slate-300 tracking-wide">Prestations de services</td>
                </tr>
                <tr class="hover:bg-slate-50 border-b border-slate-200">
                    <td class="p-2 border border-slate-200 pl-4 font-medium text-slate-600">7&nbsp;&nbsp;Imposables</td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne7_c2]" value="{{ $data['ligne7_c2'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne7_c3]" value="{{ $data['ligne7_c3'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne7_c4]" value="{{ $data['ligne7_c4'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                </tr>
                <tr class="hover:bg-slate-50 border-b border-slate-200">
                    <td class="p-2 border border-slate-200 pl-4 font-medium text-slate-600">8&nbsp;&nbsp;Exonérées à 100 %</td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne8_c2]" value="{{ $data['ligne8_c2'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne8_c3]" value="{{ $data['ligne8_c3'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne8_c4]" value="{{ $data['ligne8_c4'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                </tr>
                <tr class="hover:bg-slate-50 border-b border-slate-200">
                    <td class="p-2 border border-slate-200 pl-4 font-medium text-slate-600">9&nbsp;&nbsp;Ventes à l'export (Imposition à 17,5%)</td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne9_c2]" value="{{ $data['ligne9_c2'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne9_c3]" value="{{ $data['ligne9_c3'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne9_c4]" value="{{ $data['ligne9_c4'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                </tr>

                <tr class="bg-slate-200 font-bold text-slate-900 uppercase border-t border-slate-300">
                    <td colspan="4" class="p-2 border border-slate-300 tracking-wide">Produits et Subventions</td>
                </tr>
                <tr class="hover:bg-slate-50 border-b border-slate-200">
                    <td class="p-2 border border-slate-200 pl-4 font-medium text-slate-600">10&nbsp;&nbsp;Produits accessoires, Produits financiers, dons et libéralités</td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne10_c2]" value="{{ $data['ligne10_c2'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne10_c3]" value="{{ $data['ligne10_c3'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne10_c4]" value="{{ $data['ligne10_c4'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                </tr>
                <tr class="hover:bg-slate-50 border-b border-slate-200">
                    <td class="p-2 border border-slate-200 pl-4 font-medium text-slate-600">11&nbsp;&nbsp;Subventions d'équipement</td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne11_c2]" value="{{ $data['ligne11_c2'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne11_c3]" value="{{ $data['ligne11_c3'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne11_c4]" value="{{ $data['ligne11_c4'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                </tr>
                <tr class="hover:bg-slate-50 border-b border-slate-200">
                    <td class="p-2 border border-slate-200 pl-4 font-medium text-slate-600">12&nbsp;&nbsp;Subventions d'équilibre</td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne12_c2]" value="{{ $data['ligne12_c2'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne12_c3]" value="{{ $data['ligne12_c3'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne12_c4]" value="{{ $data['ligne12_c4'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                </tr>
                <tr class="hover:bg-slate-50 border-b border-slate-200">
                    <td class="p-2 border border-slate-200 pl-8 font-medium text-slate-600">12a&nbsp;&nbsp;Imposables</td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne12a_c2]" value="{{ $data['ligne12a_c2'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne12a_c3]" value="{{ $data['ligne12a_c3'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne12a_c4]" value="{{ $data['ligne12a_c4'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                </tr>
                <tr class="hover:bg-slate-50 border-b border-slate-200">
                    <td class="p-2 border border-slate-200 pl-8 font-medium text-slate-600">12b&nbsp;&nbsp;Exonérées à 100 %</td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne12b_c2]" value="{{ $data['ligne12b_c2'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne12b_c3]" value="{{ $data['ligne12b_c3'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne12b_c4]" value="{{ $data['ligne12b_c4'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                </tr>
                <tr class="hover:bg-slate-50 border-b border-slate-200">
                    <td class="p-2 border border-slate-200 pl-8 font-medium text-slate-600">12c&nbsp;&nbsp;Ventes à l'export</td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne12c_c2]" value="{{ $data['ligne12c_c2'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne12c_c3]" value="{{ $data['ligne12c_c3'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne12c_c4]" value="{{ $data['ligne12c_c4'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                </tr>

                <tr class="bg-slate-100 font-bold text-slate-900 border-y border-slate-400">
                    <td class="p-2 border border-slate-300 pl-4">13&nbsp;&nbsp;Totaux partiels</td>
                    <td class="p-1 border border-slate-300"><input type="text" name="f[ligne13_c2]" value="{{ $data['ligne13_c2'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-300"><input type="text" name="f[ligne13_c3]" value="{{ $data['ligne13_c3'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-300"><input type="text" name="f[ligne13_c4]" value="{{ $data['ligne13_c4'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                </tr>
                <tr class="hover:bg-slate-50 border-b border-slate-200">
                    <td class="p-2 border border-slate-200 pl-4 font-medium text-slate-600">14&nbsp;&nbsp;Profit net global des cessions après abattement pondéré</td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne14_c2]" value="{{ $data['ligne14_c2'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne14_c3]" value="{{ $data['ligne14_c3'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne14_c4]" value="{{ $data['ligne14_c4'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                </tr>
                <tr class="hover:bg-slate-50 border-b border-slate-200">
                    <td class="p-2 border border-slate-200 pl-4 font-medium text-slate-600">15&nbsp;&nbsp;Autres profits exceptionnels</td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne15_c2]" value="{{ $data['ligne15_c2'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne15_c3]" value="{{ $data['ligne15_c3'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[ligne15_c4]" value="{{ $data['ligne15_c4'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                </tr>
                <tr class="bg-slate-100 font-bold text-slate-900 border-y border-slate-400">
                    <td class="p-2 border border-slate-300 pl-4">16&nbsp;&nbsp;Total général (lignes 13 + 14 + 15)</td>
                    <td class="p-1 border border-slate-300"><input type="text" name="f[ligne16_c2]" value="{{ $data['ligne16_c2'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-300"><input type="text" name="f[ligne16_c3]" value="{{ $data['ligne16_c3'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-300"><input type="text" name="f[ligne16_c4]" value="{{ $data['ligne16_c4'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="mt-3 space-y-1">
        <p class="text-[10px] text-slate-400">(1) Faire figurer dans ces cases la moitié du montant figurant dans la colonne 3 - même ligne</p>
        <p class="text-[10px] text-slate-400">(2) Faire figurer dans ces cases la moitié du montant figurant dans la colonne 2 - même ligne</p>
    </div>
        <div class="mt-4 flex justify-end"><button type="submit" class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">💾 Enregistrer le tableau</button></div>
    </form>
</div>
@endsection
