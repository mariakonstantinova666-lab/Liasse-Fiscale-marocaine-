@extends('layouts.app')

@section('content')
@php $fmt = fn ($v) => abs((float) $v) < 0.005 ? '—' : number_format((float) $v, 2, ',', ' '); @endphp
<div class="bg-white shadow-lg rounded-sm border border-slate-200 p-6">
    <div class="liasse-page-header">
        <div class="liasse-page-heading">
            <h2 class="liasse-page-title">Tableau T22 — TABLEAU DE FINANCEMENT DE L'EXERCICE</h2>
            <div class="liasse-page-meta">
                <span class="liasse-page-meta-item liasse-page-meta-exercise">Exercice : <strong>{{ $exercice }}</strong></span>
                <span class="liasse-page-meta-item liasse-page-meta-closing">Clôture : <strong>31/12/{{ $exercice }}</strong></span>
            </div>
        </div>
        <span class="liasse-page-badge">Tableau T22</span>
    </div>

    <h3 class="font-bold text-slate-700 mt-4 mb-2">I — SYNTHÈSE DES MASSES DU BILAN</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-xs border-collapse border border-slate-300" style="min-width: 900px;">
            <thead class="text-white text-center font-bold">
                <tr><th rowspan="2" class="p-2 border bg-slate-800 text-left">Masses</th><th rowspan="2" class="p-2 border bg-slate-800">Exercice</th><th rowspan="2" class="p-2 border bg-slate-800">Exercice précédent</th><th colspan="2" class="p-2 border bg-slate-800">Variations N − N-1</th></tr>
                <tr><th class="p-2 border bg-slate-700">Emplois</th><th class="p-2 border bg-slate-700">Ressources</th></tr>
            </thead>
            <tbody>
                @foreach($synthese as $row)
                    <tr class="{{ !empty($row['total']) ? 'bg-slate-100 font-bold dark:bg-slate-800/70 dark:text-slate-100' : 'hover:bg-slate-50 dark:hover:bg-indigo-500/10' }}">
                        <td class="p-2 border border-slate-300">{!! $row['l'] !!}</td>
                        <td class="p-2 border border-slate-300 text-right font-mono">{{ $fmt($row['n']) }}</td>
                        <td class="p-2 border border-slate-300 text-right font-mono">{{ $fmt($row['p']) }}</td>
                        <td class="p-2 border border-slate-300 text-right font-mono">{{ $fmt($row['emploi']) }}</td>
                        <td class="p-2 border border-slate-300 text-right font-mono">{{ $fmt($row['ressource']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <h3 class="font-bold text-slate-700 mt-6 mb-2">II — EMPLOIS ET RESSOURCES</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-xs border-collapse border border-slate-300" style="min-width: 900px;">
            <thead class="text-white text-center font-bold">
                <tr><th rowspan="2" class="p-2 border bg-slate-800 text-left">Flux</th><th colspan="2" class="p-2 border bg-slate-800">Exercice</th><th colspan="2" class="p-2 border bg-slate-800">Exercice précédent</th></tr>
                <tr><th class="p-2 border bg-slate-700">Emplois</th><th class="p-2 border bg-slate-700">Ressources</th><th class="p-2 border bg-slate-700">Emplois</th><th class="p-2 border bg-slate-700">Ressources</th></tr>
            </thead>
            <tbody>
                @foreach($fluxRows as $row)
                    @if(!empty($row['section']))
                        <tr class="bg-slate-200 font-bold dark:bg-slate-800 dark:text-slate-100"><td colspan="5" class="p-2 border border-slate-300 dark:border-slate-600">{{ $row['section'] }}</td></tr>
                    @else
                        <tr class="{{ !empty($row['total']) ? 'bg-slate-100 font-bold dark:bg-slate-800/70 dark:text-slate-100' : 'hover:bg-slate-50 dark:hover:bg-indigo-500/10' }}">
                            <td class="p-2 border border-slate-300">{{ $row['label'] }}</td>
                            @foreach(['n_emploi','n_ressource','p_emploi','p_ressource'] as $key)
                                <td class="p-2 border border-slate-300 text-right font-mono">{{ $fmt($row[$key] ?? 0) }}</td>
                            @endforeach
                        </tr>
                    @endif
                @endforeach
                <tr class="bg-slate-800 text-white font-bold">
                    <td class="p-3 border border-slate-700">TOTAL GÉNÉRAL</td>
                    @foreach(['n_emploi','n_ressource','p_emploi','p_ressource'] as $key)
                        <td class="p-3 border border-slate-700 text-right font-mono">{{ $fmt($fluxTotal->{$key}) }}</td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>
    <p class="mt-3 text-xs text-slate-500">Les flux non identifiables dans les balances de clôture (distributions, remboursements et virements) nécessitent une source de mouvements complémentaire.</p>
</div>
@endsection
