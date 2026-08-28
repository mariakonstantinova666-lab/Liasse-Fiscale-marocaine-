@extends('layouts.app')

@section('content')
@php $fmt = fn ($v) => number_format((float) $v, 2, ',', ' '); @endphp
<div class="bg-white shadow-lg rounded-sm border border-slate-200 p-6">
    <div class="flex justify-between items-center mb-4 border-b pb-2">
        <div>
            <h2 class="text-xl font-bold text-slate-800 uppercase tracking-wider">Tableau T20 — ETAT DETAILLE DES STOCKS</h2>
            <p class="text-sm text-slate-500 mt-1">Exercice : <strong>{{ $exercice }}</strong></p>
        </div>
        <span class="text-sm font-semibold bg-slate-100 px-3 py-1 rounded text-slate-600">Tableau T20</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-xs border-collapse border border-slate-300" style="min-width: 1100px;">
            <thead class="text-white text-center font-bold">
                <tr>
                    <th rowspan="2" class="p-2 border border-slate-300 bg-slate-800 text-left">STOCKS</th>
                    <th colspan="3" class="p-2 border border-slate-300 bg-slate-800">Stock final (N)</th>
                    <th colspan="3" class="p-2 border border-slate-300 bg-slate-800">Stock initial (N-1)</th>
                    <th rowspan="2" class="p-2 border border-slate-300 bg-slate-800">Variation nette</th>
                </tr>
                <tr>
                    @foreach(['Brut', 'Provision', 'Net', 'Brut', 'Provision', 'Net'] as $titre)
                        <th class="p-2 border border-slate-300 bg-slate-700">{{ $titre }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($stockSections as $section => $lignes)
                    <tr class="bg-slate-200 font-bold uppercase">
                        <td colspan="8" class="p-2 border border-slate-300">{{ $section }}</td>
                    </tr>
                    @foreach($lignes as $ligne)
                        @if(isset($ligne['group']))
                            <tr class="bg-slate-50 italic font-semibold"><td colspan="8" class="p-2 pl-4 border border-slate-200">- {{ $ligne['group'] }}</td></tr>
                            @continue
                        @endif
                        @php($v = $ligne['values'])
                        <tr class="hover:bg-slate-50">
                            <td class="p-2 pl-6 border border-slate-200">- {{ $ligne['label'] }}</td>
                            <td class="p-2 text-right border border-slate-200">{{ $fmt($v->final_brut) }}</td>
                            <td class="p-2 text-right border border-slate-200">{{ $fmt($v->final_provision) }}</td>
                            <td class="p-2 text-right border border-slate-200 font-semibold">{{ $fmt($v->final_net) }}</td>
                            <td class="p-2 text-right border border-slate-200">{{ $fmt($v->initial_brut) }}</td>
                            <td class="p-2 text-right border border-slate-200">{{ $fmt($v->initial_provision) }}</td>
                            <td class="p-2 text-right border border-slate-200 font-semibold">{{ $fmt($v->initial_net) }}</td>
                            <td class="p-2 text-right border border-slate-200 font-semibold">{{ $fmt($v->variation) }}</td>
                        </tr>
                    @endforeach
                    @php($t = $stockTotals[$section])
                    <tr class="bg-slate-100 font-bold">
                        <td class="p-2 text-right border border-slate-300">Total</td>
                        @foreach(['final_brut','final_provision','final_net','initial_brut','initial_provision','initial_net','variation'] as $champ)
                            <td class="p-2 text-right border border-slate-300">{{ $fmt($t->{$champ}) }}</td>
                        @endforeach
                    </tr>
                @endforeach
                <tr class="bg-slate-800 text-white font-bold text-sm">
                    <td class="p-3 border border-slate-700">TOTAL GÉNÉRAL</td>
                    @foreach(['final_brut','final_provision','final_net','initial_brut','initial_provision','initial_net','variation'] as $champ)
                        <td class="p-3 text-right border border-slate-700">{{ $fmt($stockTotalGeneral->{$champ}) }}</td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
