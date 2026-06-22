@extends('layouts.app')

@section('content')
<div class="bg-white shadow-lg rounded-sm border border-slate-200 p-6">
    <div class="flex justify-between items-center mb-4 border-b pb-2">
        <div>
            <h2 class="text-xl font-bold text-slate-800 uppercase tracking-wider">
                Tableau des immobilisations autres que les immobilisations en non-valeurs
            </h2>
            <p class="text-sm text-slate-500 mt-1">Exercice : <strong>{{ $exercice ?? session('annee_exercice', 2025) }}</strong></p>
        </div>
        <span class="text-sm font-semibold bg-slate-100 px-3 py-1 rounded text-slate-600">Tableau N° 5</span>
    </div>

    @php
        $globalDebut = 0; $globalAcquisition = 0; $globalVirementAug = 0;
        $globalCession = 0; $globalVirementDim = 0; $globalFin = 0;

        if (isset($totauxImmo) && is_array($totauxImmo)) {
            foreach ($totauxImmo as $sub) {
                $globalDebut       += $sub->debut ?? 0;
                $globalAcquisition += $sub->acquisition ?? 0;
                $globalVirementAug += $sub->virement_aug ?? 0;
                $globalCession     += $sub->cession ?? 0;
                $globalVirementDim += $sub->virement_dim ?? 0;
                $globalFin         += $sub->fin ?? 0;
            }
        }
    @endphp

    <div class="overflow-x-auto">
        <table class="w-full text-xs text-left border-collapse border border-slate-300" style="min-width: 1000px;">
            <thead class="text-white text-center font-bold">
                <tr>
                    <th rowspan="2" class="p-2 border border-slate-300 align-middle bg-slate-800 text-left" style="width: 30%;">
                        NATURE DES IMMOBILISATIONS
                    </th>
                    <th rowspan="2" class="p-2 border border-slate-300 align-middle bg-slate-800">
                        MONTANT BRUT AU DÉBUT<br>DE L'EXERCICE
                    </th>
                    <th colspan="2" class="p-2 border border-slate-300 bg-emerald-700">AUGMENTATIONS</th>
                    <th colspan="2" class="p-2 border border-slate-300 bg-rose-700">DIMINUTIONS</th>
                    <th rowspan="2" class="p-2 border border-slate-300 align-middle bg-slate-800">
                        MONTANT BRUT À LA FIN<br>DE L'EXERCICE
                    </th>
                </tr>
                <tr>
                    <th class="p-2 border border-slate-300 bg-emerald-600 font-medium">Acquisitions</th>
                    <th class="p-2 border border-slate-300 bg-emerald-600 font-medium">Virements poste à poste</th>
                    <th class="p-2 border border-slate-300 bg-rose-600 font-medium">Cessions ou retraits</th>
                    <th class="p-2 border border-slate-300 bg-rose-600 font-medium">Virements poste à poste</th>
                </tr>
            </thead>
            <tbody>
                @if(isset($immoData) && (is_array($immoData) || is_object($immoData)))
                    @foreach($immoData as $rubrique => $lignes)
                        <tr class="bg-slate-200 font-bold text-slate-900 border-t border-slate-300">
                            <td colspan="7" class="p-2 border border-slate-300 uppercase tracking-wide">{{ $rubrique }}</td>
                        </tr>

                        @foreach($lignes as $nomLigne => $donnees)
                            <tr class="hover:bg-slate-50 border-b border-slate-200">
                                <td class="p-2 border border-slate-200 pl-6 font-medium text-slate-600">{{ $nomLigne }}</td>
                                <td class="p-2 border border-slate-200 text-right font-mono font-semibold text-slate-800">{{ number_format($donnees->debut ?? 0, 2, ',', ' ') }}</td>
                                <td class="p-2 border border-slate-200 text-right font-mono text-emerald-700">{{ number_format($donnees->acquisition ?? 0, 2, ',', ' ') }}</td>
                                <td class="p-2 border border-slate-200 text-right font-mono text-slate-500">{{ number_format($donnees->virement_aug ?? 0, 2, ',', ' ') }}</td>
                                <td class="p-2 border border-slate-200 text-right font-mono text-rose-700">{{ number_format($donnees->cession ?? 0, 2, ',', ' ') }}</td>
                                <td class="p-2 border border-slate-200 text-right font-mono text-slate-500">{{ number_format($donnees->virement_dim ?? 0, 2, ',', ' ') }}</td>
                                <td class="p-2 border border-slate-200 text-right font-mono font-bold text-slate-900 bg-slate-50">{{ number_format($donnees->fin ?? 0, 2, ',', ' ') }}</td>
                            </tr>
                        @endforeach

                        @if(isset($totauxImmo[$rubrique]))
                            @php $subTotal = $totauxImmo[$rubrique]; @endphp
                            <tr class="bg-slate-100 font-bold text-slate-900 border-y border-slate-400">
                                <td class="p-2 border border-slate-300 uppercase text-right pr-3 text-blue-900">TOTAL {{ $rubrique }}</td>
                                <td class="p-2 border border-slate-300 text-right font-mono text-blue-950">{{ number_format($subTotal->debut ?? 0, 2, ',', ' ') }}</td>
                                <td class="p-2 border border-slate-300 text-right font-mono text-emerald-800">{{ number_format($subTotal->acquisition ?? 0, 2, ',', ' ') }}</td>
                                <td class="p-2 border border-slate-300 text-right font-mono text-blue-950">{{ number_format($subTotal->virement_aug ?? 0, 2, ',', ' ') }}</td>
                                <td class="p-2 border border-slate-300 text-right font-mono text-rose-800">{{ number_format($subTotal->cession ?? 0, 2, ',', ' ') }}</td>
                                <td class="p-2 border border-slate-300 text-right font-mono text-blue-950">{{ number_format($subTotal->virement_dim ?? 0, 2, ',', ' ') }}</td>
                                <td class="p-2 border border-slate-300 text-right font-mono text-blue-950">{{ number_format($subTotal->fin ?? 0, 2, ',', ' ') }}</td>
                            </tr>
                        @endif
                    @endforeach
                @else
                    <tr>
                        <td colspan="7" class="p-4 text-center text-slate-500 italic">
                            Aucune ligne d'immobilisation trouvée pour cet exercice.
                        </td>
                    </tr>
                @endif
            </tbody>

            <tfoot>
                <tr class="bg-slate-800 text-white font-bold border-t-4 border-double border-slate-900 text-sm">
                    <td class="p-3 border border-slate-700 uppercase tracking-wider font-black">TOTAL GÉNÉRAL</td>
                    <td class="p-3 border border-slate-700 text-right font-mono text-yellow-400">{{ number_format($globalDebut, 2, ',', ' ') }}</td>
                    <td class="p-3 border border-slate-700 text-right font-mono text-emerald-400">{{ number_format($globalAcquisition, 2, ',', ' ') }}</td>
                    <td class="p-3 border border-slate-700 text-right font-mono text-slate-300">{{ number_format($globalVirementAug, 2, ',', ' ') }}</td>
                    <td class="p-3 border border-slate-700 text-right font-mono text-rose-400">{{ number_format($globalCession, 2, ',', ' ') }}</td>
                    <td class="p-3 border border-slate-700 text-right font-mono text-slate-300">{{ number_format($globalVirementDim, 2, ',', ' ') }}</td>
                    <td class="p-3 border border-slate-700 text-right font-mono text-green-400 text-base font-black bg-slate-700">{{ number_format($globalFin, 2, ',', ' ') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
