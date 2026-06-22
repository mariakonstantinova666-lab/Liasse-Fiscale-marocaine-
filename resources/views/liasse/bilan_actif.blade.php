@extends('layouts.app')

@section('content')
<div class="bg-white shadow-lg rounded-sm border border-slate-200 p-6">
    <div class="flex justify-between items-center mb-4 border-b pb-2">
        <h2 class="text-xl font-bold text-slate-800 uppercase tracking-wider">BILAN - ACTIF</h2>
        <span class="text-sm font-semibold bg-slate-100 px-3 py-1 rounded text-slate-600">Au 31/12/{{ $exercice }}</span>
    </div>
    
    <table class="w-full text-xs text-left border-collapse border border-slate-300">
        <thead class="bg-slate-800 text-white text-center font-bold">
            <tr>
                <th class="p-2 border text-left" style="width: 45%;">Éléments</th>
                <th class="p-2 border" style="width: 13%;">Brut</th>
                <th class="p-2 border" style="width: 13%;">Amort. & Prov.</th>
                <th class="p-2 border" style="width: 14%;">Net</th>
                <th class="p-2 border bg-slate-700" style="width: 15%;">Exercice Précédent</th>
            </tr>
        </thead>
        <tbody>
            @php
                $grandTotalBrut = 0;
                $grandTotalAmort = 0;
                $grandTotalNet = 0;
                $grandTotalNetPrec = 0;
            @endphp

            @foreach($data as $rubrique => $lignes)
                @php
                    // Calcul du total de la catégorie parente (lignes en gras)
                    $sectionBrut = 0; $sectionAmort = 0; $sectionNet = 0; $sectionNetPrec = 0;
                    foreach($lignes as $l) {
                        $sectionBrut += $l->brut;
                        $sectionAmort += $l->amort;
                        $sectionNet += $l->net;
                        $sectionNetPrec += $l->net_prec ?? 0;
                    }
                @endphp
                {{-- En-tête de section avec cumul dynamique --}}
                <tr class="bg-slate-200 font-bold text-slate-900 border-t border-slate-300">
                    <td class="p-2 border uppercase tracking-wide">{{ $rubrique }}</td>
                    <td class="p-2 border text-right font-mono">{{ $sectionBrut > 0 ? number_format($sectionBrut, 2, ',', ' ') : '0,00' }}</td>
                    <td class="p-2 border text-right font-mono">{{ $sectionAmort > 0 ? number_format($sectionAmort, 2, ',', ' ') : '0,00' }}</td>
                    <td class="p-2 border text-right font-mono text-blue-900">{{ number_format($sectionNet, 2, ',', ' ') }}</td>
                    <td class="p-2 border text-right font-mono bg-slate-100 text-slate-700">{{ number_format($sectionNetPrec, 2, ',', ' ') }}</td>
                </tr>

                {{-- Éléments enfants --}}
                @foreach($lignes as $libelle => $valeurs)
                    <tr class="hover:bg-slate-50 border-b border-slate-200">
                        <td class="p-2 border pl-8 font-medium text-slate-600">{{ $libelle }}</td>
                        <td class="p-2 border text-right font-mono text-slate-700">{{ $valeurs->brut > 0 ? number_format($valeurs->brut, 2, ',', ' ') : '0,00' }}</td>
                        <td class="p-2 border text-right font-mono text-slate-500">{{ $valeurs->amort > 0 ? number_format($valeurs->amort, 2, ',', ' ') : '0,00' }}</td>
                        <td class="p-2 border text-right font-mono font-bold text-slate-800">{{ number_format($valeurs->net, 2, ',', ' ') }}</td>
                        <td class="p-2 border text-right font-mono bg-slate-50 text-slate-600">{{ number_format($valeurs->net_prec ?? 0, 2, ',', ' ') }}</td>
                    </tr>
                @endforeach

                {{-- Rendu du TOTAL I après les Écarts de conversion ( e ) --}}
                @if($rubrique === 'ECARTS DE CONVERSION - ACTIF ( e )')
                    <tr class="bg-slate-300 font-bold text-slate-900 border-y-2 border-slate-500">
                        <td class="p-2.5 border uppercase text-right pr-4 font-black">TOTAL I ( a + b + c + d + e )</td>
                        <td class="p-2.5 border text-right font-mono text-blue-950">{{ number_format($totaux['TOTAL_I']->brut, 2, ',', ' ') }}</td>
                        <td class="p-2.5 border text-right font-mono text-blue-950">{{ number_format($totaux['TOTAL_I']->amort, 2, ',', ' ') }}</td>
                        <td class="p-2.5 border text-right font-mono text-blue-950">{{ number_format($totaux['TOTAL_I']->net, 2, ',', ' ') }}</td>
                        <td class="p-2.5 border text-right font-mono bg-slate-200 text-slate-700">{{ number_format($totaux['TOTAL_I']->net_prec ?? 0, 2, ',', ' ') }}</td>
                    </tr>
                    @php
                        $grandTotalBrut += $totaux['TOTAL_I']->brut;
                        $grandTotalAmort += $totaux['TOTAL_I']->amort;
                        $grandTotalNet += $totaux['TOTAL_I']->net;
                        $grandTotalNetPrec += $totaux['TOTAL_I']->net_prec ?? 0;
                    @endphp
                @endif

                {{-- Rendu du TOTAL II après les Écarts de conversion circulants ( i ) --}}
                @if($rubrique === 'ECART DE CONVERSION - ACTIF ( i ) (Elém. Circul.)')
                    <tr class="bg-slate-300 font-bold text-slate-900 border-y-2 border-slate-500">
                        <td class="p-2.5 border uppercase text-right pr-4 font-black">TOTAL II ( f + g + h + i )</td>
                        <td class="p-2.5 border text-right font-mono text-blue-950">{{ number_format($totaux['TOTAL_II']->brut, 2, ',', ' ') }}</td>
                        <td class="p-2.5 border text-right font-mono text-blue-950">{{ number_format($totaux['TOTAL_II']->amort, 2, ',', ' ') }}</td>
                        <td class="p-2.5 border text-right font-mono text-blue-950">{{ number_format($totaux['TOTAL_II']->net, 2, ',', ' ') }}</td>
                        <td class="p-2.5 border text-right font-mono bg-slate-200 text-slate-700">{{ number_format($totaux['TOTAL_II']->net_prec ?? 0, 2, ',', ' ') }}</td>
                    </tr>
                    @php
                        $grandTotalBrut += $totaux['TOTAL_II']->brut;
                        $grandTotalAmort += $totaux['TOTAL_II']->amort;
                        $grandTotalNet += $totaux['TOTAL_II']->net;
                        $grandTotalNetPrec += $totaux['TOTAL_II']->net_prec ?? 0;
                    @endphp
                @endif

                {{-- Rendu du TOTAL III après la Trésorerie-Actif --}}
                @if($rubrique === 'TRESORERIE - ACTIF')
                    <tr class="bg-slate-300 font-bold text-slate-900 border-y-2 border-slate-500">
                        <td class="p-2.5 border uppercase text-right pr-4 font-black">TOTAL III</td>
                        <td class="p-2.5 border text-right font-mono text-blue-950">{{ number_format($totaux['TOTAL_III']->brut, 2, ',', ' ') }}</td>
                        <td class="p-2.5 border text-right font-mono text-blue-950">{{ number_format($totaux['TOTAL_III']->amort, 2, ',', ' ') }}</td>
                        <td class="p-2.5 border text-right font-mono text-blue-950">{{ number_format($totaux['TOTAL_III']->net, 2, ',', ' ') }}</td>
                        <td class="p-2.5 border text-right font-mono bg-slate-200 text-slate-700">{{ number_format($totaux['TOTAL_III']->net_prec ?? 0, 2, ',', ' ') }}</td>
                    </tr>
                    @php
                        $grandTotalBrut += $totaux['TOTAL_III']->brut;
                        $grandTotalAmort += $totaux['TOTAL_III']->amort;
                        $grandTotalNet += $totaux['TOTAL_III']->net;
                        $grandTotalNetPrec += $totaux['TOTAL_III']->net_prec ?? 0;
                    @endphp
                @endif
            @endforeach

            {{-- Ligne finale du TOTAL GÉNÉRAL (Image 6) --}}
            <tr class="bg-slate-800 text-white font-bold border-t-4 border-double border-slate-900 text-sm">
                <td class="p-3 border uppercase text-right pr-4 tracking-wider font-black">TOTAL GÉNÉRAL I + II + III</td>
                <td class="p-3 border text-right font-mono text-yellow-400 font-bold">{{ number_format($grandTotalBrut, 2, ',', ' ') }}</td>
                <td class="p-3 border text-right font-mono text-yellow-400 font-bold">{{ number_format($grandTotalAmort, 2, ',', ' ') }}</td>
                <td class="p-3 border text-right font-mono text-green-400 text-base font-black">{{ number_format($grandTotalNet, 2, ',', ' ') }}</td>
                <td class="p-3 border text-right font-mono bg-slate-700 text-slate-200">{{ number_format($grandTotalNetPrec, 2, ',', ' ') }}</td>
            </tr>
        </tbody>
    </table>
</div>
@endsection