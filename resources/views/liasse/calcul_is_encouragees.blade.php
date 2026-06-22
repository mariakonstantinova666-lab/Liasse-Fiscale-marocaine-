@extends('layouts.app')

@section('content')
<div class="bg-white shadow-lg rounded-sm border border-slate-200 p-6">
    <form method="POST" action="{{ route('liasse.save', 'calcul_is_encouragees') }}">
    @csrf
    <div class="flex justify-between items-center mb-4 border-b pb-2">
        <div>
            <h2 class="text-xl font-bold text-slate-800 uppercase tracking-wider">
                État pour le calcul de l'impôt sur les sociétés — Entreprises encouragées
            </h2>
            <p class="text-sm text-slate-500 mt-1">Exercice : <strong>{{ $exercice ?? session('annee_exercice', 2025) }}</strong></p>
        </div>
        <span class="text-sm font-semibold bg-slate-100 px-3 py-1 rounded text-slate-600">Tableau N° 26 — Exercice {{ $exercice ?? session('annee_exercice', 2025) }}</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-xs text-left border-collapse border border-slate-300" style="min-width: 900px;">
            <thead class="text-white text-center font-bold">
                <tr>
                    <th class="p-2 border border-slate-300 bg-slate-800 text-left" style="width: 60%;">Nature des Produits</th>
                    <th class="p-2 border border-slate-300 bg-slate-800">Taux</th>
                    <th class="p-2 border border-slate-300 bg-slate-800">Montant</th>
                </tr>
                <tr>
                    <th class="p-1 border border-slate-300 bg-slate-700 font-medium text-center">1</th>
                    <th class="p-1 border border-slate-300 bg-slate-700 font-medium">2</th>
                    <th class="p-1 border border-slate-300 bg-slate-700 font-medium">3</th>
                </tr>
            </thead>
            <tbody>
                <tr class="hover:bg-slate-50 border-b border-slate-200">
                    <td class="p-2 border border-slate-200 font-medium text-slate-600">1&nbsp;&nbsp;CA taxable</td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[is_ca_taxable_taux]" value="{{ $data['is_ca_taxable_taux'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[is_ca_taxable_montant]" value="{{ $data['is_ca_taxable_montant'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                </tr>
                <tr class="hover:bg-slate-50 border-b border-slate-200">
                    <td class="p-2 border border-slate-200 font-medium text-slate-600">2&nbsp;&nbsp;CA exonéré à 100%</td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[is_ca_exonere_taux]" value="{{ $data['is_ca_exonere_taux'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[is_ca_exonere_montant]" value="{{ $data['is_ca_exonere_montant'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                </tr>
                <tr class="hover:bg-slate-50 border-b border-slate-200">
                    <td class="p-2 border border-slate-200 font-medium text-slate-600">3&nbsp;&nbsp;CA soumis au taux réduit</td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[is_ca_taux_reduit_taux]" value="{{ $data['is_ca_taux_reduit_taux'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[is_ca_taux_reduit_montant]" value="{{ $data['is_ca_taux_reduit_montant'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                </tr>
                <tr class="hover:bg-slate-50 border-b border-slate-200">
                    <td class="p-2 border border-slate-200 font-medium text-slate-600">4&nbsp;&nbsp;Autres produits taxables</td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[is_autres_taxables_taux]" value="{{ $data['is_autres_taxables_taux'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[is_autres_taxables_montant]" value="{{ $data['is_autres_taxables_montant'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                </tr>
                <tr class="hover:bg-slate-50 border-b border-slate-200">
                    <td class="p-2 border border-slate-200 pl-6 font-medium text-slate-600">Autres produits d'exploitation</td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[is_autres_exploitation_taux]" value="{{ $data['is_autres_exploitation_taux'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[is_autres_exploitation_montant]" value="{{ $data['is_autres_exploitation_montant'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                </tr>
                <tr class="hover:bg-slate-50 border-b border-slate-200">
                    <td class="p-2 border border-slate-200 pl-6 font-medium text-slate-600">Produits financiers</td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[is_produits_financiers_taux]" value="{{ $data['is_produits_financiers_taux'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[is_produits_financiers_montant]" value="{{ $data['is_produits_financiers_montant'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                </tr>
                <tr class="hover:bg-slate-50 border-b border-slate-200">
                    <td class="p-2 border border-slate-200 pl-6 font-medium text-slate-600">Subventions</td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[is_subventions_taux]" value="{{ $data['is_subventions_taux'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-200"><input type="text" name="f[is_subventions_montant]" value="{{ $data['is_subventions_montant'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                </tr>
                <tr class="bg-slate-100 font-bold text-slate-900 border-y border-slate-400">
                    <td class="p-2 border border-slate-300">5&nbsp;&nbsp;Dénominateur (1 + 2 + 3 + 4)</td>
                    <td class="p-1 border border-slate-300"><input type="text" name="f[is_denominateur_taux]" value="{{ $data['is_denominateur_taux'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-300"><input type="text" name="f[is_denominateur_montant]" value="{{ $data['is_denominateur_montant'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                </tr>
                <tr class="bg-slate-100 font-bold text-slate-900 border-y border-slate-400">
                    <td class="p-2 border border-slate-300">6&nbsp;&nbsp;Montant de l'impôt sur les sociétés (IS) dû</td>
                    <td class="p-1 border border-slate-300"><input type="text" name="f[is_montant_du_taux]" value="{{ $data['is_montant_du_taux'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-300"><input type="text" name="f[is_montant_du_montant]" value="{{ $data['is_montant_du_montant'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="mt-3 space-y-1">
        <p class="text-[10px] text-slate-400">Montant de la cotisation minimale : (Montant lignes 1 + 3 + 4) x taux de la cotisation minimale.</p>
        <p class="text-[10px] text-slate-400">Montant de l'IS correspondant au taux réduit : ((Bénéfice fiscal x Montant ligne 3) / Montant ligne 5) x taux réduit de l'IS</p>
        <p class="text-[10px] text-slate-400">Montant de l'IS correspondant au taux normal : ((Bénéfice fiscal x Montant ligne 1 + 4) / Montant ligne 5) x taux normal de l'IS</p>
        <p class="text-[10px] text-slate-400">Total de l'IS dû : Montant de l'IS correspondant au taux réduit + Montant de l'IS correspondant au taux normal.</p>
    </div>

    <div class="mt-4 flex justify-end"><button type="submit" class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">💾 Enregistrer le tableau</button></div>
    </form>
</div>
@endsection
