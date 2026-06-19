@extends('layouts.app')

@section('content')
<div class="bg-white shadow-lg rounded-sm border border-slate-200 p-6">
    <div class="flex justify-between items-center mb-4 border-b pb-2">
        <h2 class="text-xl font-bold text-slate-800 uppercase tracking-wider">BILAN - PASSIF</h2>
        <span class="text-sm font-semibold bg-slate-100 px-3 py-1 rounded text-slate-600">Au 31/12/{{ $exercice }}</span>
    </div>
    
    <table class="w-full text-xs text-left border-collapse border border-slate-300">
        <thead class="bg-slate-800 text-white text-center font-bold">
            <tr>
                <th class="p-2 border text-left" style="width: 50%;">Éléments</th>
                <th class="p-2 border" style="width: 25%;">Montant Exercice (N)</th>
                <th class="p-2 border bg-slate-700" style="width: 25%;">Exercice Précédent</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $grandTotalPassif = 0; 
            @endphp

            @foreach($data as $rubrique => $lignes)
                @php
                    // Calcul en temps réel du total de la sous-section pour la ligne grisée parente
                    $sectionMontant = 0;
                    foreach($lignes as $l) {
                        $sectionMontant += $l->montant;
                    }
                @endphp
                {{-- Ligne d'en-tête de la sous-section --}}
                <tr class="bg-slate-200 font-bold text-slate-900 border-t border-slate-300">
                    <td class="p-2 border uppercase tracking-wide">{{ $rubrique }}</td>
                    <td class="p-2 border text-right font-mono text-blue-900">{{ number_format($sectionMontant, 2, ',', ' ') }}</td>
                    <td class="p-2 border text-right font-mono bg-slate-100 text-slate-400">0,00</td>
                </tr>

                {{-- Affichage de toutes les lignes enfants --}}
                @foreach($lignes as $libelle => $valeurs)
                    <tr class="hover:bg-slate-50 border-b border-slate-200">
                        <td class="p-2 border pl-8 font-medium text-slate-600">{{ $libelle }}</td>
                        <td class="p-2 border text-right font-mono font-bold text-slate-800">
                            {{ number_format($valeurs->montant, 2, ',', ' ') }}
                        </td>
                        <td class="p-2 border text-right font-mono bg-slate-50 text-slate-400">0,00</td>
                    </tr>
                @endforeach

                {{-- Bloc de rendu du TOTAL I --}}
                @if($rubrique === 'ECARTS DE CONVERSION - PASSIF ( e )')
                    <tr class="bg-slate-300 font-bold text-slate-900 border-y-2 border-slate-500">
                        <td class="p-2.5 border uppercase text-right pr-4 font-black">TOTAL I ( a + b + c + d + e )</td>
                        <td class="p-2.5 border text-right font-mono text-blue-950 font-black">{{ number_format($totaux['TOTAL_I']->montant, 2, ',', ' ') }}</td>
                        <td class="p-2.5 border text-right font-mono bg-slate-200 text-slate-400">0,00</td>
                    </tr>
                    @php $grandTotalPassif += $totaux['TOTAL_I']->montant; @endphp
                @endif

                {{-- Bloc de rendu du TOTAL II --}}
                @if($rubrique === 'ECARTS DE CONVERSION - PASSIF ( h ) (Éléments Circulants)')
                    <tr class="bg-slate-300 font-bold text-slate-900 border-y-2 border-slate-500">
                        <td class="p-2.5 border uppercase text-right pr-4 font-black">TOTAL II ( f + g + h )</td>
                        <td class="p-2.5 border text-right font-mono text-blue-950 font-black">{{ number_format($totaux['TOTAL_II']->montant, 2, ',', ' ') }}</td>
                        <td class="p-2.5 border text-right font-mono bg-slate-200 text-slate-400">0,00</td>
                    </tr>
                    @php $grandTotalPassif += $totaux['TOTAL_II']->montant; @endphp
                @endif

                {{-- Bloc de rendu du TOTAL III --}}
                @if($rubrique === 'TRESORERIE PASSIF')
                    <tr class="bg-slate-300 font-bold text-slate-900 border-y-2 border-slate-500">
                        <td class="p-2.5 border uppercase text-right pr-4 font-black">TOTAL III</td>
                        <td class="p-2.5 border text-right font-mono text-blue-950 font-black">{{ number_format($totaux['TOTAL_III']->montant, 2, ',', ' ') }}</td>
                        <td class="p-2.5 border text-right font-mono bg-slate-200 text-slate-400">0,00</td>
                    </tr>
                    @php $grandTotalPassif += $totaux['TOTAL_III']->montant; @endphp
                @endif
            @endforeach

            {{-- Ligne finale du TOTAL GÉNÉRAL DU PASSIF --}}
            <tr class="bg-slate-800 text-white font-bold border-t-4 border-double border-slate-900 text-sm">
                <td class="p-3 border uppercase text-right pr-4 tracking-wider font-black">TOTAL GÉNÉRAL I + II + III</td>
                <td class="p-3 border text-right font-mono text-green-400 text-base font-black">{{ number_format($grandTotalPassif, 2, ',', ' ') }}</td>
                <td class="p-3 border text-right font-mono bg-slate-700 text-slate-300">0,00</td>
            </tr>
        </tbody>
    </table>
</div>
@endsection