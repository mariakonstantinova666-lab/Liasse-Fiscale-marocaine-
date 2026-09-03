@extends('layouts.app')

@section('content')
@php $fmt = fn ($v) => number_format((float) ($v ?? 0), 2, ',', ' '); @endphp
<div class="bg-white shadow-lg rounded-sm border border-slate-200 p-6">
    <div class="liasse-page-header">
        <div class="liasse-page-heading">
            <h2 class="liasse-page-title">
                Tableau T05 — ETAT DES SOLDES INTERMEDIAIRES DE GESTION (E.S.G)
            </h2>
            <div class="liasse-page-meta">
                <span class="liasse-page-meta-item liasse-page-meta-exercise">Exercice : <strong>{{ $exercice }}</strong></span>
                <span class="liasse-page-meta-item liasse-page-meta-closing">Clôture : <strong>31/12/{{ $exercice }}</strong></span>
            </div>
        </div>
        <span class="liasse-page-badge">Tableau T05</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-xs text-left border-collapse border border-slate-300" style="min-width: 900px;">
            <thead class="text-white text-center font-bold">
                <tr>
                    <th class="p-2 border border-slate-300 align-middle bg-slate-800 text-left" style="width: 60%;">Eléments</th>
                    <th class="p-2 border border-slate-300 align-middle bg-slate-800">Exercice</th>
                    <th class="p-2 border border-slate-300 align-middle bg-slate-800">Exercice Précédent</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    @if(isset($row['section']))
                        <tr class="bg-slate-200 font-bold text-slate-900 border-t border-slate-300">
                            <td colspan="3" class="p-2 border border-slate-300 uppercase tracking-wide">{{ $row['section'] }}</td>
                        </tr>
                    @elseif(!empty($row['total']))
                        <tr class="bg-slate-100 font-bold text-slate-900 border-y border-slate-400">
                            <td class="p-2 border border-slate-300 pl-4">{{ $row['l'] }}</td>
                            <td class="p-2 border border-slate-300 text-right font-mono">{{ $fmt($n[$row['k']] ?? 0) }}</td>
                            <td class="p-2 border border-slate-300 text-right font-mono">{{ $fmt($p[$row['k']] ?? 0) }}</td>
                        </tr>
                    @else
                        <tr class="hover:bg-slate-50 border-b border-slate-200">
                            <td class="p-2 border border-slate-200 {{ !empty($row['indent']) ? 'pl-8' : 'pl-4' }} {{ !empty($row['bold']) ? 'font-bold text-slate-800' : 'font-medium text-slate-600' }}">{{ $row['l'] }}</td>
                            <td class="p-2 border border-slate-200 text-right font-mono {{ !empty($row['bold']) ? 'font-bold text-slate-800' : '' }}">{{ $fmt($n[$row['k']] ?? 0) }}</td>
                            <td class="p-2 border border-slate-200 text-right font-mono {{ !empty($row['bold']) ? 'font-bold text-slate-800' : '' }}">{{ $fmt($p[$row['k']] ?? 0) }}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
