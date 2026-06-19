@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Tableau N°6 : TABLEAU DES PROVISIONS</h1>
            <p class="text-sm text-gray-500">Exercice : {{ $exercice }}</p>
        </div>
        <button class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg shadow transition">
            Enregistrer le T6
        </button>
    </div>

    <div class="bg-white rounded-xl shadow border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-xs text-gray-700">
                <thead class="bg-gray-50 text-gray-700 uppercase font-semibold tracking-wider border-b border-gray-200">
                    <tr>
                        <th scope="col" rowspan="2" class="px-4 py-4 text-left w-1/4 border-r border-gray-200 align-middle">Nature</th>
                        <th scope="col" rowspan="2" class="px-2 py-4 text-right border-r border-gray-200 align-middle">Montant début exercice<br><span class="text-gray-400 font-normal lowercase">(1)</span></th>
                        <th scope="col" colspan="3" class="px-2 py-2 text-center border-r border-gray-200 border-b">DOTATIONS</th>
                        <th scope="col" colspan="3" class="px-2 py-2 text-center border-r border-gray-200 border-b">REPRISES</th>
                        <th scope="col" rowspan="2" class="px-2 py-4 text-right align-middle bg-blue-50/50">Montant fin exercice<br><span class="text-gray-400 font-normal lowercase">(8 = 1+2+3+4-5-6-7)</span></th>
                    </tr>
                    <tr>
                        <th scope="col" class="px-2 py-2 text-right border-r border-gray-200 font-normal lowercase text-gray-500">Exploitat. (2)</th>
                        <th scope="col" class="px-2 py-2 text-right border-r border-gray-200 font-normal lowercase text-gray-500">Financ. (3)</th>
                        <th scope="col" class="px-2 py-2 text-right border-r border-gray-200 font-normal lowercase text-gray-500">N. Cour. (4)</th>
                        <th scope="col" class="px-2 py-2 text-right border-r border-gray-200 font-normal lowercase text-gray-500">Exploitat. (5)</th>
                        <th scope="col" class="px-2 py-2 text-right border-r border-gray-200 font-normal lowercase text-gray-500">Financ. (6)</th>
                        <th scope="col" class="px-2 py-2 text-right border-r border-gray-200 font-normal lowercase text-gray-500">N. Cour. (7)</th>
                    </tr>
                </thead>
                
                <tbody class="divide-y divide-gray-200">
                    @foreach($provisionsData as $rubrique => $lignes)
                        {{-- Ligne de Sous-Total de Rubrique --}}
                        <tr class="bg-gray-100 font-bold text-gray-900 border-t-2 border-gray-300">
                            <td class="px-4 py-2.5 border-r border-gray-200">{{ $rubrique }}</td>
                            <td class="px-2 py-2.5 text-right border-r border-gray-200">{{ number_format($totauxProvisions[$rubrique]->col1, 2, '.', ' ') }}</td>
                            <td class="px-2 py-2.5 text-right border-r border-gray-200">{{ number_format($totauxProvisions[$rubrique]->col2, 2, '.', ' ') }}</td>
                            <td class="px-2 py-2.5 text-right border-r border-gray-200">{{ number_format($totauxProvisions[$rubrique]->col3, 2, '.', ' ') }}</td>
                            <td class="px-2 py-2.5 text-right border-r border-gray-200">{{ number_format($totauxProvisions[$rubrique]->col4, 2, '.', ' ') }}</td>
                            <td class="px-2 py-2.5 text-right border-r border-gray-200">{{ number_format($totauxProvisions[$rubrique]->col5, 2, '.', ' ') }}</td>
                            <td class="px-2 py-2.5 text-right border-r border-gray-200">{{ number_format($totauxProvisions[$rubrique]->col6, 2, '.', ' ') }}</td>
                            <td class="px-2 py-2.5 text-right border-r border-gray-200">{{ number_format($totauxProvisions[$rubrique]->col7, 2, '.', ' ') }}</td>
                            <td class="px-2 py-2.5 text-right font-black bg-blue-100/40">
                                @php
                                    $subTotalFin = $totauxProvisions[$rubrique]->col1 + $totauxProvisions[$rubrique]->col2 + $totauxProvisions[$rubrique]->col3 + $totauxProvisions[$rubrique]->col4 - $totauxProvisions[$rubrique]->col5 - $totauxProvisions[$rubrique]->col6 - $totauxProvisions[$rubrique]->col7;
                                @endphp
                                {{ number_format($subTotalFin, 2, '.', ' ') }}
                            </td>
                        </tr>

                        {{-- Lignes de Détails --}}
                        @foreach($lignes as $libelle => $valeurs)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-2 text-gray-600 pl-10 border-r border-gray-200 font-medium">{{ $libelle }}</td>
                                <td class="px-2 py-2 text-right text-gray-400 border-r border-gray-200">{{ number_format($valeurs->col1, 2, '.', ' ') }}</td>
                                <td class="px-2 py-2 text-right text-gray-400 border-r border-gray-200">{{ number_format($valeurs->col2, 2, '.', ' ') }}</td>
                                <td class="px-2 py-2 text-right text-gray-400 border-r border-gray-200">{{ number_format($valeurs->col3, 2, '.', ' ') }}</td>
                                <td class="px-2 py-2 text-right text-gray-400 border-r border-gray-200">{{ number_format($valeurs->col4, 2, '.', ' ') }}</td>
                                <td class="px-2 py-2 text-right text-gray-400 border-r border-gray-200">{{ number_format($valeurs->col5, 2, '.', ' ') }}</td>
                                <td class="px-2 py-2 text-right text-gray-400 border-r border-gray-200">{{ number_format($valeurs->col6, 2, '.', ' ') }}</td>
                                <td class="px-2 py-2 text-right text-gray-400 border-r border-gray-200">{{ number_format($valeurs->col7, 2, '.', ' ') }}</td>
                                <td class="px-2 py-2 text-right font-semibold text-gray-900 bg-blue-50/30">
                                    @php
                                        $ligneFin = $valeurs->col1 + $valeurs->col2 + $valeurs->col3 + $valeurs->col4 - $valeurs->col5 - $valeurs->col6 - $valeurs->col7;
                                    @endphp
                                    {{ number_format($ligneFin, 2, '.', ' ') }}
                                </td>
                            </tr>
                        @endforeach
                    @endforeach

                    {{-- TOTAL GÉNÉRAL --}}
                    <tr class="bg-gray-800 text-white font-bold border-t-4 border-gray-900">
                        <td class="px-4 py-3 uppercase tracking-wider text-sm">TOTAL GÉNÉRAL</td>
                        <td class="px-2 py-3 text-right">{{ number_format($totalGeneral->col1, 2, '.', ' ') }}</td>
                        <td class="px-2 py-3 text-right">{{ number_format($totalGeneral->col2, 2, '.', ' ') }}</td>
                        <td class="px-2 py-3 text-right">{{ number_format($totalGeneral->col3, 2, '.', ' ') }}</td>
                        <td class="px-2 py-3 text-right">{{ number_format($totalGeneral->col4, 2, '.', ' ') }}</td>
                        <td class="px-2 py-3 text-right">{{ number_format($totalGeneral->col5, 2, '.', ' ') }}</td>
                        <td class="px-2 py-3 text-right">{{ number_format($totalGeneral->col6, 2, '.', ' ') }}</td>
                        <td class="px-2 py-3 text-right">{{ number_format($totalGeneral->col7, 2, '.', ' ') }}</td>
                        <td class="px-2 py-3 text-right text-yellow-400 font-extrabold text-sm bg-gray-900">
                            @php
                                $genFin = $totalGeneral->col1 + $totalGeneral->col2 + $totalGeneral->col3 + $totalGeneral->col4 - $totalGeneral->col5 - $totalGeneral->col6 - $totalGeneral->col7;
                            @endphp
                            {{ number_format($genFin, 2, '.', ' ') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection