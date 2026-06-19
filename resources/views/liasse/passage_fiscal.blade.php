@extends('layouts.app') {{-- Ou ton layout d'origine si tu en as un --}}

@section('content')
<div class="container mx-auto p-6">
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        
        <div class="mb-6 border-b pb-4">
            <h2 class="text-xl font-bold text-gray-800">TABLEAU N° 4 : PASSAGE DU RESULTAT NET COMPTABLE AU RESULTAT NET FISCAL</h2>
            <p class="text-sm text-gray-500 font-medium">EXERCICE : {{ $exercice }}</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-gray-300 text-sm">
                <thead>
                    <tr class="bg-gray-100 text-gray-700 font-bold">
                        <th class="border border-gray-300 p-3 text-left w-1/2">INTITULES</th>
                        <th class="border border-gray-300 p-3 text-right w-1/4">REINTEGRATIONS</th>
                        <th class="border border-gray-300 p-3 text-right w-1/4">DEDUCTIONS</th>
                    </tr>
                </thead>
                <tbody class="text-gray-800 divide-y divide-gray-200">

                    <tr class="bg-gray-50 font-bold text-gray-900">
                        <td class="border border-gray-300 p-2.5" colspan="3">I. RESULTAT NET COMPTABLE</td>
                    </tr>
                    <tr>
                        <td class="border border-gray-300 p-2 pl-6">Bénéfice net</td>
                        <td class="border border-gray-300 p-2 text-right font-mono text-blue-600">
                            {{ number_format($fiscalData['I. RESULTAT NET COMPTABLE']['Bénéfice net'], 2, '.', ' ') }}
                        </td>
                        <td class="border border-gray-300 p-2 bg-gray-50 text-center text-gray-400">---</td>
                    </tr>
                    <tr>
                        <td class="border border-gray-300 p-2 pl-6">Perte nette</td>
                        <td class="border border-gray-300 p-2 bg-gray-50 text-center text-gray-400">---</td>
                        <td class="border border-gray-300 p-2 text-right font-mono text-red-600">
                            {{ number_format($fiscalData['I. RESULTAT NET COMPTABLE']['Perte nette'], 2, '.', ' ') }}
                        </td>
                    </tr>

                    <tr class="bg-gray-50 font-bold text-gray-900">
                        <td class="border border-gray-300 p-2.5" colspan="3">II. REINTEGRATIONS FISCALES</td>
                    </tr>
                    <tr>
                        <td class="border border-gray-300 p-2 pl-6">1. Courantes</td>
                        <td class="border border-gray-300 p-2 text-right font-mono">
                            {{ number_format($fiscalData['II. REINTEGRATIONS FISCALES']['1. Courantes'], 2, '.', ' ') }}
                        </td>
                        <td class="border border-gray-300 p-2 bg-gray-50 text-center text-gray-400">---</td>
                    </tr>
                    <tr>
                        <td class="border border-gray-300 p-2 pl-6">2. Non courantes</td>
                        <td class="border border-gray-300 p-2 text-right font-mono">
                            {{ number_format($fiscalData['II. REINTEGRATIONS FISCALES']['2. Non courantes'], 2, '.', ' ') }}
                        </td>
                        <td class="border border-gray-300 p-2 bg-gray-50 text-center text-gray-400">---</td>
                    </tr>

                    <tr class="bg-gray-50 font-bold text-gray-900">
                        <td class="border border-gray-300 p-2.5" colspan="3">III. DEDUCTIONS FISCALES</td>
                    </tr>
                    <tr>
                        <td class="border border-gray-300 p-2 pl-6">1. Courantes</td>
                        <td class="border border-gray-300 p-2 bg-gray-50 text-center text-gray-400">---</td>
                        <td class="border border-gray-300 p-2 text-right font-mono">
                            {{ number_format($fiscalData['III. DEDUCTIONS FISCALES']['1. Courantes'], 2, '.', ' ') }}
                        </td>
                    </tr>
                    <tr>
                        <td class="border border-gray-300 p-2 pl-6">2. Non courantes</td>
                        <td class="border border-gray-300 p-2 bg-gray-50 text-center text-gray-400">---</td>
                        <td class="border border-gray-300 p-2 text-right font-mono">
                            {{ number_format($fiscalData['III. DEDUCTIONS FISCALES']['2. Non courantes'], 2, '.', ' ') }}
                        </td>
                    </tr>

                    <tr class="bg-yellow-50 font-bold text-gray-900">
                        <td class="border border-gray-300 p-2.5 text-center">TOTAL (I + II + III)</td>
                        <td class="border border-gray-300 p-2 text-right font-mono">
                            {{ number_format($fiscalData['SYNTHESE_TOTAL']['Total Réintégrations'], 2, '.', ' ') }}
                        </td>
                        <td class="border border-gray-300 p-2 text-right font-mono">
                            {{ number_format($fiscalData['SYNTHESE_TOTAL']['Total Déductions'], 2, '.', ' ') }}
                        </td>
                    </tr>

                    <tr class="bg-gray-50 font-bold text-gray-900">
                        <td class="border border-gray-300 p-2.5" colspan="3">IV. RESULTAT BRUT FISCAL</td>
                    </tr>
                    <tr>
                        <td class="border border-gray-300 p-2 pl-6">Bénéfice brut si T1 > T2 (A)</td>
                        <td class="border border-gray-300 p-2 text-right font-mono text-green-600 font-bold">
                            {{ number_format($fiscalData['IV. RESULTAT BRUT FISCAL']['Bénéfice brut si T1 > T2 (A)'], 2, '.', ' ') }}
                        </td>
                        <td class="border border-gray-300 p-2 bg-gray-50 text-center text-gray-400">---</td>
                    </tr>
                    <tr>
                        <td class="border border-gray-300 p-2 pl-6">Déficit brut fiscal si T2 > T1 (B)</td>
                        <td class="border border-gray-300 p-2 bg-gray-50 text-center text-gray-400">---</td>
                        <td class="border border-gray-300 p-2 text-right font-mono text-red-600 font-bold">
                            {{ number_format($fiscalData['IV. RESULTAT BRUT FISCAL']['Déficit brut fiscal si T2 > T1 (B)'], 2, '.', ' ') }}
                        </td>
                    </tr>

                    <tr class="bg-gray-50 font-bold text-gray-900">
                        <td class="border border-gray-300 p-2.5" colspan="3">V. REPORTS DEFICITAIRES IMPUTES (C)</td>
                    </tr>
                    @foreach(['n-4', 'n-3', 'n-2', 'n-1'] as $key)
                    <tr>
                        <td class="border border-gray-300 p-2 pl-6">Exercice {{ $key }}</td>
                        <td class="border border-gray-300 p-2 bg-gray-50 text-center text-gray-400">---</td>
                        <td class="border border-gray-300 p-2 text-right font-mono">
                            {{ number_format($fiscalData['V. REPORTS DEFICITAIRES IMPUTES (C)']['Exercice ' . $key . ' (' . ($exercice - intval(substr($key, 2))) . ')'], 2, '.', ' ') }}
                        </td>
                    </tr>
                    @endforeach

                    <tr class="bg-gray-50 font-bold text-gray-900">
                        <td class="border border-gray-300 p-2.5" colspan="3">VI. RESULTAT NET FISCAL</td>
                    </tr>
                    <tr>
                        <td class="border border-gray-300 p-2 pl-6">Bénéfice net fiscal (A - C)</td>
                        <td class="border border-gray-300 p-2 text-right font-mono font-bold text-green-700">
                            {{ number_format($fiscalData['VI. RESULTAT NET FISCAL']['Bénéfice net fiscal (A-C)'], 2, '.', ' ') }}
                        </td>
                        <td class="border border-gray-300 p-2 bg-gray-50 text-center text-gray-400">---</td>
                    </tr>
                    <tr>
                        <td class="border border-gray-300 p-2 pl-6">ou Déficit net fiscal (B)</td>
                        <td class="border border-gray-300 p-2 bg-gray-50 text-center text-gray-400">---</td>
                        <td class="border border-gray-300 p-2 text-right font-mono font-bold text-red-700">
                            {{ number_format($fiscalData['VI. RESULTAT NET FISCAL']['ou déficit net fiscal (B)'], 2, '.', ' ') }}
                        </td>
                    </tr>

                    <tr class="bg-gray-50 font-bold text-gray-900">
                        <td class="border border-gray-300 p-2.5">VII. CUMUL DES AMORTISSEMENTS FISCALEMENT DIFFERES</td>
                        <td class="border border-gray-300 p-2 text-right font-mono">
                            {{ number_format($fiscalData['VII. CUMUL DES AMORTISSEMENTS FISCALEMENT DIFFERES']['Montant'], 2, '.', ' ') }}
                        </td>
                        <td class="border border-gray-300 p-2 bg-gray-50 text-center text-gray-400">---</td>
                    </tr>

                    <tr class="bg-gray-50 font-bold text-gray-900">
                        <td class="border border-gray-300 p-2.5" colspan="3">VIII. CUMUL DES DEFICITS FISCAUX RESTANT A REPORTER</td>
                    </tr>
                    @foreach(['n-4', 'n-3', 'n-2', 'n-1'] as $key)
                    <tr>
                        <td class="border border-gray-300 p-2 pl-6">Exercice {{ $key }}</td>
                        <td class="border border-gray-300 p-2 bg-gray-50 text-center text-gray-400">---</td>
                        <td class="border border-gray-300 p-2 text-right font-mono">
                            {{ number_format($fiscalData['VIII. CUMUL DES DEFICITS FISCAUX RESTANT A REPORTER']['Exercice ' . $key . ' (' . ($exercice - intval(substr($key, 2))) . ')'], 2, '.', ' ') }}
                        </td>
                    </tr>
                    @endforeach

                </tbody>
            </table>
        </div>

    </div>
</div>
@endsection