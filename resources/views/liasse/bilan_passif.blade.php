@extends('layouts.app')

@section('content')
@php $fmt = fn ($v) => number_format((float) $v, 2, ',', ' '); @endphp
<div class="bg-white shadow-lg rounded-sm border border-slate-200 p-6">
    <div class="flex justify-between items-center mb-4 border-b pb-2">
        <h2 class="text-xl font-bold text-slate-800 uppercase tracking-wider">Tableau T01-B — BILAN - PASSIF</h2>
        <span class="text-sm font-semibold bg-slate-100 px-3 py-1 rounded text-slate-600">Au 31/12/{{ $exercice }}</span>
    </div>

    <table class="w-full text-xs text-left border-collapse border border-slate-300">
        <thead class="bg-slate-800 text-white text-center font-bold">
            <tr><th class="p-2 border text-left w-1/2">Éléments</th><th class="p-2 border">Exercice N</th><th class="p-2 border bg-slate-700">Exercice N-1</th></tr>
        </thead>
        <tbody>
            @foreach($data as $rubrique => $lignes)
                @php
                    $sectionN = collect($lignes)->sum(fn ($v) => $v->montant);
                    $sectionP = collect($lignes)->sum(fn ($v) => $v->montant_prec);
                @endphp
                <tr class="bg-slate-200 font-bold text-slate-900 border-t border-slate-300">
                    <td class="p-2 border uppercase tracking-wide">{{ $rubrique }}</td>
                    <td class="p-2 border text-right font-mono text-blue-900">{{ $fmt($sectionN) }}</td>
                    <td class="p-2 border text-right font-mono text-blue-900">{{ $fmt($sectionP) }}</td>
                </tr>
                @foreach($lignes as $libelle => $v)
                    <tr class="hover:bg-slate-50 border-b border-slate-200">
                        <td class="p-2 border pl-8 font-medium text-slate-600">{{ $libelle }}</td>
                        <td class="p-2 border text-right font-mono font-bold">{{ $fmt($v->montant) }}</td>
                        <td class="p-2 border text-right font-mono">{{ $fmt($v->montant_prec) }}</td>
                    </tr>
                @endforeach

                @if($rubrique === 'ECARTS DE CONVERSION - PASSIF ( e )')
                    @php
                        $total = $totaux['TOTAL_I'];
                    @endphp
                    <tr class="bg-slate-300 font-black"><td class="p-2.5 border text-right">TOTAL I (a+b+c+d+e)</td><td class="p-2.5 border text-right">{{ $fmt($total->montant) }}</td><td class="p-2.5 border text-right">{{ $fmt($total->montant_prec) }}</td></tr>
                @endif
                @if($rubrique === 'ECARTS DE CONVERSION - PASSIF ( h ) (Éléments Circulants)')
                    @php
                        $total = $totaux['TOTAL_II'];
                    @endphp
                    <tr class="bg-slate-300 font-black"><td class="p-2.5 border text-right">TOTAL II (f+g+h)</td><td class="p-2.5 border text-right">{{ $fmt($total->montant) }}</td><td class="p-2.5 border text-right">{{ $fmt($total->montant_prec) }}</td></tr>
                @endif
                @if($rubrique === 'TRESORERIE PASSIF')
                    @php
                        $total = $totaux['TOTAL_III'];
                    @endphp
                    <tr class="bg-slate-300 font-black"><td class="p-2.5 border text-right">TOTAL III</td><td class="p-2.5 border text-right">{{ $fmt($total->montant) }}</td><td class="p-2.5 border text-right">{{ $fmt($total->montant_prec) }}</td></tr>
                @endif
            @endforeach
            @php
                $grandN = $totaux['TOTAL_I']->montant + $totaux['TOTAL_II']->montant + $totaux['TOTAL_III']->montant;
                $grandP = $totaux['TOTAL_I']->montant_prec + $totaux['TOTAL_II']->montant_prec + $totaux['TOTAL_III']->montant_prec;
            @endphp
            <tr class="bg-slate-800 text-white font-black text-sm">
                <td class="p-3 border text-right">TOTAL GÉNÉRAL I + II + III</td><td class="p-3 border text-right text-green-400">{{ $fmt($grandN) }}</td><td class="p-3 border text-right">{{ $fmt($grandP) }}</td>
            </tr>
        </tbody>
    </table>
</div>
@endsection
