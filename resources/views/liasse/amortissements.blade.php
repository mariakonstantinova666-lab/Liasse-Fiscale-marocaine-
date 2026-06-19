@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Tableau N°5 : TABLEAU DES AMORTISSEMENTS</h1>
            <p class="text-sm text-gray-500">Exercice : {{ $exercice }}</p>
        </div>
        <button class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg shadow transition">
            Enregistrer le T5
        </button>
    </div>

    <div class="bg-white rounded-xl shadow border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-gray-700 uppercase font-semibold text-xs tracking-wider">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left w-1/3 border-r border-gray-200">Nature</th>
                        <th scope="col" class="px-4 py-4 text-right border-r border-gray-200">Cumul début exercice<br><span class="text-gray-400 font-normal lowercase">(1)</span></th>
                        <th scope="col" class="px-4 py-4 text-right border-r border-gray-200">Dotation de l'exercice<br><span class="text-gray-400 font-normal lowercase">(2)</span></th>
                        <th scope="col" class="px-4 py-4 text-right border-r border-gray-200">Amortissements sur immo. sorties<br><span class="text-gray-400 font-normal lowercase">(3)</span></th>
                        <th scope="col" class="px-4 py-4 text-right">Cumul d'amortissement fin exercice<br><span class="text-gray-400 font-normal lowercase">(4 = 1 + 2 - 3)</span></th>
                    </tr>
                </thead>
                
                <tbody class="divide-y divide-gray-200 text-gray-700">
                    @foreach($amortData as $rubrique => $lignes)
                        <tr class="bg-gray-100 font-bold text-gray-900 border-t-2 border-gray-300">
                            <td class="px-6 py-3 border-r border-gray-200">{{ $rubrique }}</td>
                            <td class="px-4 py-3 text-right border-r border-gray-200">{{ number_format($totauxAmort[$rubrique]->col1, 2, '.', ' ') }}</td>
                            <td class="px-4 py-3 text-right border-r border-gray-200">{{ number_format($totauxAmort[$rubrique]->col2, 2, '.', ' ') }}</td>
                            <td class="px-4 py-3 text-right border-r border-gray-200">{{ number_format($totauxAmort[$rubrique]->col3, 2, '.', ' ') }}</td>
                            <td class="px-4 py-3 text-right font-black">{{ number_format($totauxAmort[$rubrique]->col4, 2, '.', ' ') }}</td>
                        </tr>

                        @foreach($lignes as $libelle => $valeurs)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-8 py-2.5 text-gray-600 pl-12 border-r border-gray-200">{{ $libelle }}</td>
                                <td class="px-4 py-2.5 text-right text-gray-400 border-r border-gray-200">
                                    {{ number_format($valeurs->col1, 2, '.', ' ') }}
                                </td>
                                <td class="px-4 py-2.5 text-right text-gray-900 border-r border-gray-200 font-medium">
                                    {{ number_format($valeurs->col2, 2, '.', ' ') }}
                                </td>
                                <td class="px-4 py-2.5 text-right text-gray-400 border-r border-gray-200">
                                    {{ number_format($valeurs->col3, 2, '.', ' ') }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-semibold text-gray-900 bg-blue-50/30">
                                    {{ number_format($valeurs->col4, 2, '.', ' ') }}
                                </td>
                            </tr>
                        @endforeach
                    @endforeach

                    <tr class="bg-gray-800 text-white font-bold text-base border-t-4 border-gray-900">
                        <td class="px-6 py-4 uppercase tracking-wider">TOTAL GÉNÉRAL</td>
                        <td class="px-4 py-4 text-right">{{ number_format($totalGeneral->col1, 2, '.', ' ') }}</td>
                        <td class="px-4 py-4 text-right">{{ number_format($totalGeneral->col2, 2, '.', ' ') }}</td>
                        <td class="px-4 py-4 text-right">{{ number_format($totalGeneral->col3, 2, '.', ' ') }}</td>
                        <td class="px-4 py-4 text-right text-yellow-400 font-extrabold">{{ number_format($totalGeneral->col4, 2, '.', ' ') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection