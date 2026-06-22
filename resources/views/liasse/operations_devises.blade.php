@extends('layouts.app')

@section('content')
<div class="bg-white shadow-lg rounded-sm border border-slate-200 p-6">
    <form method="POST" action="{{ route('liasse.save', 'operations_devises') }}">
    @csrf
    <div class="flex justify-between items-center mb-4 border-b pb-2">
        <div>
            <h2 class="text-xl font-bold text-slate-800 uppercase tracking-wider">
                Tableau des opérations en devises comptabilisées pendant l'exercice
            </h2>
            <p class="text-sm text-slate-500 mt-1">Exercice : <strong>{{ $exercice ?? session('annee_exercice', 2025) }}</strong></p>
        </div>
        <span class="text-sm font-semibold bg-slate-100 px-3 py-1 rounded text-slate-600">Tableau N° 21 — Exercice {{ $exercice ?? session('annee_exercice', 2025) }}</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-xs text-left border-collapse border border-slate-300" style="min-width: 700px;">
            <thead class="text-white text-center font-bold">
                <tr>
                    <th class="p-2 border border-slate-300 align-middle bg-slate-800 text-left" style="width: 40%;">Nature</th>
                    <th class="p-2 border border-slate-300 align-middle bg-slate-800">Entrée (Contre-valeur en DH)</th>
                    <th class="p-2 border border-slate-300 align-middle bg-slate-800">Sortie (Contre-valeur en DH)</th>
                </tr>
            </thead>
            <tbody>
                @foreach([
                    'Financement permanent',
                    'Immobilisations brutes',
                    'Rentrées sur immobilisations',
                    'Remboursement des dettes de financement',
                    'Produits',
                    'Charges',
                ] as $label)
                    <tr class="hover:bg-slate-50 border-b border-slate-200">
                        <td class="p-2 border border-slate-200 font-medium text-slate-600">{{ $label }}</td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[r{{ $loop->index }}_c2]" value="{{ $data['r'.$loop->index.'_c2'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[r{{ $loop->index }}_c3]" value="{{ $data['r'.$loop->index.'_c3'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    </tr>
                @endforeach

                <tr class="bg-slate-100 font-bold text-slate-900 border-y border-slate-400">
                    <td class="p-2 border border-slate-300 uppercase text-right pr-3 text-blue-900">Total Des Entrées</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                </tr>
                <tr class="bg-slate-100 font-bold text-slate-900 border-y border-slate-400">
                    <td class="p-2 border border-slate-300 uppercase text-right pr-3 text-blue-900">Total Des Sorties</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                </tr>
                <tr class="bg-slate-100 font-bold text-slate-900 border-y border-slate-400">
                    <td class="p-2 border border-slate-300 uppercase text-right pr-3 text-blue-900">Balance Devises</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                </tr>
                <tr class="bg-slate-200 font-bold text-slate-900 border-y border-slate-400">
                    <td class="p-2 border border-slate-300 uppercase text-right pr-3 tracking-wide">Total</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="mt-4 flex justify-end"><button type="submit" class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">💾 Enregistrer le tableau</button></div>
    </form>
</div>
@endsection
