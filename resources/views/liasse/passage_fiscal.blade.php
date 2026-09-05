@extends('layouts.app') {{-- Ou ton layout d'origine si tu en as un --}}

@section('content')
<div class="container mx-auto p-6">
    <div class="liasse-page-header">
        <div class="liasse-page-heading">
            <h2 class="liasse-page-title">Tableau T03 — PASSAGE DU RESULTAT NET COMPTABLE AU RESULTAT NET FISCAL</h2>
            <div class="liasse-page-meta">
                <span class="liasse-page-meta-item liasse-page-meta-exercise">Exercice : <strong>{{ $exercice }}</strong></span>
                <span class="liasse-page-meta-item liasse-page-meta-closing">Clôture : <strong>31/12/{{ $exercice }}</strong></span>
            </div>
        </div>
        <span class="liasse-page-badge">Tableau T03</span>
    </div>

    <form method="POST" action="{{ route('liasse.save', 'passage_fiscal') }}">
        @csrf
    <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-gray-300 text-sm">
                <thead>
                    <tr class="bg-gray-100 text-gray-700 font-bold">
                        <th class="border border-gray-300 p-3 text-left w-1/2">INTITULES</th>
                        <th class="border border-gray-300 p-3 text-right w-1/4">Montant</th>
                        <th class="border border-gray-300 p-3 text-right w-1/4">Montant</th>
                    </tr>
                </thead>
                <tbody class="text-gray-800 divide-y divide-gray-200">

                    <tr class="bg-gray-50 font-bold text-gray-900">
                        <td class="border border-gray-300 p-2.5" colspan="3">I. RESULTAT NET COMPTABLE</td>
                    </tr>
                    <tr>
                        <td class="border border-gray-300 p-2 pl-6">Bénéfice net</td>
                        <td class="border border-gray-300 p-2 text-right font-mono text-blue-600 dark:text-blue-300">
                            {{ number_format($fiscalData['I. RESULTAT NET COMPTABLE']['Bénéfice net'], 2, '.', ' ') }}
                        </td>
                        <td class="border border-gray-300 p-2 bg-gray-50 text-center text-gray-400">---</td>
                    </tr>
                    <tr>
                        <td class="border border-gray-300 p-2 pl-6">Perte nette</td>
                        <td class="border border-gray-300 p-2 bg-gray-50 text-center text-gray-400">---</td>
                        <td class="border border-gray-300 p-2 text-right font-mono text-red-600 dark:text-red-300">
                            {{ number_format($fiscalData['I. RESULTAT NET COMPTABLE']['Perte nette'], 2, '.', ' ') }}
                        </td>
                    </tr>

                    <tr class="bg-gray-50 font-bold text-gray-900">
                        <td class="border border-gray-300 p-2.5" colspan="3">II. REINTEGRATIONS FISCALES</td>
                    </tr>
                    <tr>
                        <td class="border border-gray-300 p-2 pl-6">1. Courantes</td>
                        <td class="border border-gray-300 p-2 text-right font-mono">
                            <input type="text" inputmode="decimal" name="f[reintegrations_courantes_total]" value="{{ $sourceData['reintegrations_courantes_total'] ?? $fiscalData['II. REINTEGRATIONS FISCALES']['1. Courantes'] }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded">
                        </td>
                        <td class="border border-gray-300 p-2 bg-gray-50 text-center text-gray-400">---</td>
                    </tr>
                    @foreach($fiscalData['DETAIL_REINTEGRATIONS']['Courantes'] ?? [] as $detail)
                            <tr class="bg-yellow-50 dark:[&>td]:!border-slate-700 dark:[&>td]:!bg-slate-800 dark:[&>td]:!text-slate-100">
                                <td class="border border-gray-300 p-2 pl-10 text-sm"><input type="text" name="f[reintegration_courante_0_label]" value="{{ $sourceData['reintegration_courante_0_label'] ?? $detail['label'] }}" class="w-full bg-transparent text-left px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                                <td class="border border-gray-300 p-2 text-right font-mono">
                                    <input type="text" inputmode="decimal" name="f[reintegration_courante_0_montant]" value="{{ $sourceData['reintegration_courante_0_montant'] ?? $detail['montant'] }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded">
                                </td>
                                <td class="border border-gray-300 p-2 bg-gray-50 text-center text-gray-400">---</td>
                            </tr>
                    @endforeach
                    <tr>
                        <td class="border border-gray-300 p-2 pl-6">2. Non courantes</td>
                        <td class="border border-gray-300 p-2 text-right font-mono">
                            <input type="text" inputmode="decimal" name="f[reintegrations_non_courantes_total]" value="{{ $sourceData['reintegrations_non_courantes_total'] ?? $fiscalData['II. REINTEGRATIONS FISCALES']['2. Non courantes'] }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded">
                        </td>
                        <td class="border border-gray-300 p-2 bg-gray-50 text-center text-gray-400">---</td>
                    </tr>
                    @foreach($fiscalData['DETAIL_REINTEGRATIONS']['Non courantes'] ?? [] as $detail)
                            <tr class="bg-yellow-50 dark:[&>td]:!border-slate-700 dark:[&>td]:!bg-slate-800 dark:[&>td]:!text-slate-100">
                                <td class="border border-gray-300 p-2 pl-10 text-sm"><input type="text" name="f[reintegration_non_courante_0_label]" value="{{ $sourceData['reintegration_non_courante_0_label'] ?? $detail['label'] }}" class="w-full bg-transparent text-left px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                                <td class="border border-gray-300 p-2 text-right font-mono">
                                    <input type="text" inputmode="decimal" name="f[reintegration_non_courante_0_montant]" value="{{ $sourceData['reintegration_non_courante_0_montant'] ?? $detail['montant'] }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded">
                                </td>
                                <td class="border border-gray-300 p-2 bg-gray-50 text-center text-gray-400">---</td>
                            </tr>
                    @endforeach

                    <tr class="bg-gray-50 font-bold text-gray-900">
                        <td class="border border-gray-300 p-2.5" colspan="3">III. DEDUCTIONS FISCALES</td>
                    </tr>
                    <tr>
                        <td class="border border-gray-300 p-2 pl-6">1. Courantes</td>
                        <td class="border border-gray-300 p-2 bg-gray-50 text-center text-gray-400">---</td>
                        <td class="border border-gray-300 p-2 text-right font-mono">
                            <input type="text" inputmode="decimal" name="f[deductions_courantes_total]" value="{{ $sourceData['deductions_courantes_total'] ?? $fiscalData['III. DEDUCTIONS FISCALES']['1. Courantes'] }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded">
                        </td>
                    </tr>
                    <tr>
                        <td class="border border-gray-300 p-2 pl-6">2. Non courantes</td>
                        <td class="border border-gray-300 p-2 bg-gray-50 text-center text-gray-400">---</td>
                        <td class="border border-gray-300 p-2 text-right font-mono">
                            <input type="text" inputmode="decimal" name="f[deductions_non_courantes_total]" value="{{ $sourceData['deductions_non_courantes_total'] ?? $fiscalData['III. DEDUCTIONS FISCALES']['2. Non courantes'] }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded">
                        </td>
                    </tr>

                    <tr class="bg-yellow-50 font-bold text-gray-900 dark:[&>td]:!border-slate-600 dark:[&>td]:!bg-slate-800 dark:[&>td]:!text-slate-100">
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
                        <td class="border border-gray-300 p-2 bg-gray-50 text-center text-gray-400">---</td>
                        <td class="border border-gray-300 p-2 text-right font-mono text-green-600 font-bold dark:text-green-300">
                            {{ number_format($fiscalData['IV. RESULTAT BRUT FISCAL']['Bénéfice brut si T1 > T2 (A)'], 2, '.', ' ') }}
                        </td>
                    </tr>
                    <tr>
                        <td class="border border-gray-300 p-2 pl-6">Déficit brut fiscal si T2 > T1 (B)</td>
                        <td class="border border-gray-300 p-2 bg-gray-50 text-center text-gray-400">---</td>
                        <td class="border border-gray-300 p-2 text-right font-mono text-red-600 font-bold dark:text-red-300">
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
                            @if($key === 'n-1')
                                <input type="text" inputmode="decimal" name="f[reports_deficitaires_total]" value="{{ $sourceData['reports_deficitaires_total'] ?? $fiscalData['V. REPORTS DEFICITAIRES IMPUTES (C)']['Exercice ' . $key . ' (' . ($exercice - intval(substr($key, 2))) . ')'] }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded">
                            @else
                                {{ number_format($fiscalData['V. REPORTS DEFICITAIRES IMPUTES (C)']['Exercice ' . $key . ' (' . ($exercice - intval(substr($key, 2))) . ')'], 2, '.', ' ') }}
                            @endif
                        </td>
                    </tr>
                    @endforeach

                    <tr class="bg-gray-50 font-bold text-gray-900">
                        <td class="border border-gray-300 p-2.5" colspan="3">VI. RESULTAT NET FISCAL</td>
                    </tr>
                    <tr>
                        <td class="border border-gray-300 p-2 pl-6">Bénéfice net fiscal (A - C)</td>
                        <td class="border border-gray-300 p-2 bg-gray-50 text-center text-gray-400">---</td>
                        <td class="border border-gray-300 p-2 text-right font-mono font-bold text-green-700 dark:text-green-300">
                            {{ number_format($fiscalData['VI. RESULTAT NET FISCAL']['Bénéfice net fiscal (A-C)'], 2, '.', ' ') }}
                        </td>
                    </tr>
                    <tr>
                        <td class="border border-gray-300 p-2 pl-6">ou Déficit net fiscal (B)</td>
                        <td class="border border-gray-300 p-2 bg-gray-50 text-center text-gray-400">---</td>
                        <td class="border border-gray-300 p-2 text-right font-mono font-bold text-red-700 dark:text-red-300">
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
        <div class="mt-6 flex justify-end">
            <button type="submit" class="px-5 py-2 bg-blue-600 text-white font-semibold rounded hover:bg-blue-700">💾 Enregistrer le tableau</button>
        </div>
    </form>
</div>
@endsection



