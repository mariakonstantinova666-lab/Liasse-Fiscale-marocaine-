@extends('layouts.app')

@section('content')
<div class="bg-white shadow-lg rounded-sm border border-slate-200 p-6">
    <form method="POST" action="{{ route('liasse.save', 'interets_emprunts') }}">
    @csrf
    <div class="flex justify-between items-center mb-4 border-b pb-2">
        <div>
            <h2 class="text-xl font-bold text-slate-800 uppercase tracking-wider">
                État des intérêts des emprunts contractés auprès des associés et des tiers autres que les organismes de banque ou de crédit
            </h2>
            <p class="text-sm text-slate-500 mt-1">Exercice : <strong>{{ $exercice ?? session('annee_exercice', 2025) }}</strong></p>
        </div>
        <span class="text-sm font-semibold bg-slate-100 px-3 py-1 rounded text-slate-600">Tableau N° 18 — Exercice {{ $exercice ?? session('annee_exercice', 2025) }}</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-xs text-left border-collapse border border-slate-300" style="min-width: 1600px;">
            <thead class="text-white text-center font-bold">
                <tr>
                    <th class="p-2 border border-slate-300 align-middle bg-slate-800">Nom, prénoms</th>
                    <th class="p-2 border border-slate-300 align-middle bg-slate-800">Raison sociale</th>
                    <th class="p-2 border border-slate-300 align-middle bg-slate-800">Adresse</th>
                    <th class="p-2 border border-slate-300 align-middle bg-slate-800">N° IF</th>
                    <th class="p-2 border border-slate-300 align-middle bg-slate-800">N° CIN</th>
                    <th class="p-2 border border-slate-300 align-middle bg-slate-800">Montant du prêt</th>
                    <th class="p-2 border border-slate-300 align-middle bg-slate-800">Date du prêt</th>
                    <th class="p-2 border border-slate-300 align-middle bg-slate-800">Durée du prêt en mois</th>
                    <th class="p-2 border border-slate-300 align-middle bg-slate-800">Taux d'intérêt annuel</th>
                    <th class="p-2 border border-slate-300 align-middle bg-slate-800">Charge financière globale</th>
                    <th class="p-2 border border-slate-300 align-middle bg-slate-800">Remboursement exercices antérieurs</th>
                    <th class="p-2 border border-slate-300 align-middle bg-slate-800">Remboursement exercice actuel</th>
                    <th class="p-2 border border-slate-300 align-middle bg-slate-800">Observations</th>
                </tr>
            </thead>
            <tbody>
                <tr class="bg-slate-200 font-bold text-slate-900 border-t border-slate-300 uppercase">
                    <td colspan="13" class="p-2 border border-slate-300 tracking-wide">A. Associés</td>
                </tr>
                @for($i = 0; $i < 5; $i++)
                    <tr class="hover:bg-slate-50 border-b border-slate-200">
                        <td class="p-1 border border-slate-200"><input type="text" name="f[assoc{{ $i }}_c1]" value="{{ $data['assoc'.$i.'_c1'] ?? '' }}" class="w-full bg-transparent text-left font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[assoc{{ $i }}_c2]" value="{{ $data['assoc'.$i.'_c2'] ?? '' }}" class="w-full bg-transparent text-left font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[assoc{{ $i }}_c3]" value="{{ $data['assoc'.$i.'_c3'] ?? '' }}" class="w-full bg-transparent text-left font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[assoc{{ $i }}_c4]" value="{{ $data['assoc'.$i.'_c4'] ?? '' }}" class="w-full bg-transparent text-left font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[assoc{{ $i }}_c5]" value="{{ $data['assoc'.$i.'_c5'] ?? '' }}" class="w-full bg-transparent text-left font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[assoc{{ $i }}_c6]" value="{{ $data['assoc'.$i.'_c6'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[assoc{{ $i }}_c7]" value="{{ $data['assoc'.$i.'_c7'] ?? '' }}" class="w-full bg-transparent text-left font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[assoc{{ $i }}_c8]" value="{{ $data['assoc'.$i.'_c8'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[assoc{{ $i }}_c9]" value="{{ $data['assoc'.$i.'_c9'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[assoc{{ $i }}_c10]" value="{{ $data['assoc'.$i.'_c10'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[assoc{{ $i }}_c11]" value="{{ $data['assoc'.$i.'_c11'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[assoc{{ $i }}_c12]" value="{{ $data['assoc'.$i.'_c12'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[assoc{{ $i }}_c13]" value="{{ $data['assoc'.$i.'_c13'] ?? '' }}" class="w-full bg-transparent text-left font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    </tr>
                @endfor

                <tr class="bg-slate-200 font-bold text-slate-900 border-t border-slate-300 uppercase">
                    <td colspan="13" class="p-2 border border-slate-300 tracking-wide">B. Tiers</td>
                </tr>
                @for($i = 0; $i < 5; $i++)
                    <tr class="hover:bg-slate-50 border-b border-slate-200">
                        <td class="p-1 border border-slate-200"><input type="text" name="f[tiers{{ $i }}_c1]" value="{{ $data['tiers'.$i.'_c1'] ?? '' }}" class="w-full bg-transparent text-left font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[tiers{{ $i }}_c2]" value="{{ $data['tiers'.$i.'_c2'] ?? '' }}" class="w-full bg-transparent text-left font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[tiers{{ $i }}_c3]" value="{{ $data['tiers'.$i.'_c3'] ?? '' }}" class="w-full bg-transparent text-left font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[tiers{{ $i }}_c4]" value="{{ $data['tiers'.$i.'_c4'] ?? '' }}" class="w-full bg-transparent text-left font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[tiers{{ $i }}_c5]" value="{{ $data['tiers'.$i.'_c5'] ?? '' }}" class="w-full bg-transparent text-left font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[tiers{{ $i }}_c6]" value="{{ $data['tiers'.$i.'_c6'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[tiers{{ $i }}_c7]" value="{{ $data['tiers'.$i.'_c7'] ?? '' }}" class="w-full bg-transparent text-left font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[tiers{{ $i }}_c8]" value="{{ $data['tiers'.$i.'_c8'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[tiers{{ $i }}_c9]" value="{{ $data['tiers'.$i.'_c9'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[tiers{{ $i }}_c10]" value="{{ $data['tiers'.$i.'_c10'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[tiers{{ $i }}_c11]" value="{{ $data['tiers'.$i.'_c11'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[tiers{{ $i }}_c12]" value="{{ $data['tiers'.$i.'_c12'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[tiers{{ $i }}_c13]" value="{{ $data['tiers'.$i.'_c13'] ?? '' }}" class="w-full bg-transparent text-left font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    </tr>
                @endfor

                <tr class="bg-slate-100 font-bold text-slate-900 border-y border-slate-400">
                    <td colspan="5" class="p-2 border border-slate-300 uppercase text-right pr-3 text-blue-900">Total</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300">&nbsp;</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="mt-4 flex justify-end"><button type="submit" class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">💾 Enregistrer le tableau</button></div>
    </form>
</div>
@endsection
