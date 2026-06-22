@extends('layouts.app')

@section('content')
@php $fmt = fn ($v) => abs((float) $v) < 0.005 ? '&nbsp;' : number_format((float) $v, 2, ',', ' '); @endphp
<div class="bg-white shadow-lg rounded-sm border border-slate-200 p-6">
    <div class="flex justify-between items-center mb-4 border-b pb-2">
        <div>
            <h2 class="text-xl font-bold text-slate-800 uppercase tracking-wider">
                Tableau de financement de l'exercice
            </h2>
            <p class="text-sm text-slate-500 mt-1">Exercice : <strong>{{ $exercice }}</strong></p>
        </div>
        <span class="text-sm font-semibold bg-slate-100 px-3 py-1 rounded text-slate-600">Tableau N° 22 — Exercice {{ $exercice }}</span>
    </div>

    {{-- PARTIE I --}}
    <h3 class="font-bold text-slate-700 mt-4 mb-2">I — SYNTHÈSE DES MASSES DU BILAN</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-xs text-left border-collapse border border-slate-300" style="min-width: 900px;">
            <thead class="text-white text-center font-bold">
                <tr>
                    <th rowspan="2" class="p-2 border border-slate-300 align-middle bg-slate-800 text-left" style="width: 36%;">Masses</th>
                    <th rowspan="2" class="p-2 border border-slate-300 align-middle bg-slate-800">Exercice (a)</th>
                    <th rowspan="2" class="p-2 border border-slate-300 align-middle bg-slate-800">Exercice précédent (b)</th>
                    <th colspan="2" class="p-2 border border-slate-300 bg-slate-800">Variations a-b</th>
                </tr>
                <tr>
                    <th class="p-2 border border-slate-300 bg-slate-700 font-medium">Emplois (c)</th>
                    <th class="p-2 border border-slate-300 bg-slate-700 font-medium">Ressources (d)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($synthese as $row)
                    @if(!empty($row['total']))
                        <tr class="bg-slate-100 font-bold text-slate-900 border-y border-slate-400">
                            <td class="p-2 border border-slate-300">{!! $row['l'] !!}</td>
                            <td class="p-2 border border-slate-300 text-right font-mono">{!! $fmt($row['n']) !!}</td>
                            <td class="p-2 border border-slate-300 text-right font-mono">{!! $fmt($row['p']) !!}</td>
                            <td class="p-2 border border-slate-300 text-right font-mono">{!! $fmt($row['emploi']) !!}</td>
                            <td class="p-2 border border-slate-300 text-right font-mono">{!! $fmt($row['ressource']) !!}</td>
                        </tr>
                    @else
                        <tr class="hover:bg-slate-50 border-b border-slate-200">
                            <td class="p-2 border border-slate-200 font-medium text-slate-600">{!! $row['l'] !!}</td>
                            <td class="p-2 border border-slate-200 text-right font-mono">{!! $fmt($row['n']) !!}</td>
                            <td class="p-2 border border-slate-200 text-right font-mono">{!! $fmt($row['p']) !!}</td>
                            <td class="p-2 border border-slate-200 text-right font-mono">{!! $fmt($row['emploi']) !!}</td>
                            <td class="p-2 border border-slate-200 text-right font-mono">{!! $fmt($row['ressource']) !!}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- PARTIE II --}}
    <h3 class="font-bold text-slate-700 mt-6 mb-2">II — EMPLOIS ET RESSOURCES</h3>
    <p class="text-[11px] text-amber-700 bg-amber-50 border border-amber-200 rounded px-2 py-1 mb-2">
        La Partie I (synthèse des masses) est calculée automatiquement à partir des balances N et N-1.
        La Partie II (flux détaillés) nécessite des données de mouvements (acquisitions, cessions, remboursements)
        non déductibles d'une simple balance : à compléter en saisie.
    </p>
    <div class="overflow-x-auto">
        <table class="w-full text-xs text-left border-collapse border border-slate-300" style="min-width: 900px;">
            <thead class="text-white text-center font-bold">
                <tr>
                    <th rowspan="2" class="p-2 border border-slate-300 align-middle bg-slate-800 text-left" style="width: 40%;">&nbsp;</th>
                    <th colspan="2" class="p-2 border border-slate-300 bg-slate-800">Exercice</th>
                    <th colspan="2" class="p-2 border border-slate-300 bg-slate-800">Exercice précédent</th>
                </tr>
                <tr>
                    <th class="p-2 border border-slate-300 bg-slate-700 font-medium">Emplois</th>
                    <th class="p-2 border border-slate-300 bg-slate-700 font-medium">Ressources</th>
                    <th class="p-2 border border-slate-300 bg-slate-700 font-medium">Emplois</th>
                    <th class="p-2 border border-slate-300 bg-slate-700 font-medium">Ressources</th>
                </tr>
            </thead>
            <tbody>
                {{-- SECTION I --}}
                <tr class="bg-slate-200 font-bold text-slate-900 border-t border-slate-300">
                    <td colspan="5" class="p-2 border border-slate-300 uppercase tracking-wide">I- RESSOURCES STABLES DE L'EXERCICE (FLUX)</td>
                </tr>
                @foreach([
                    'Autofinancement (A)',
                    '+ Capacité d\'autofinancement',
                    '- Distributions de bénéfices',
                    'Cessions et réductions d\'immobilisations (B)',
                    '+ Cessions d\'immobilisations incorporelles',
                    '+ Cessions d\'immobilisations corporelles',
                    '+ Cessions d\'immobilisations financières',
                    '+ Récupérations sur créances immobilisées',
                    'Augmentation des capitaux propres et assimilés (C)',
                    '+ Augmentation du capital, apports',
                    '+ Subventions d\'investissement',
                    'Augmentation des dettes de financement (D) (1)',
                ] as $ligne)
                <tr class="hover:bg-slate-50 border-b border-slate-200">
                    <td class="p-2 border border-slate-200 font-medium text-slate-600">{{ $ligne }}</td>
                    <td class="p-2 border border-slate-200 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-200 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-200 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-200 text-right font-mono">&nbsp;</td>
                </tr>
                @endforeach
                <tr class="bg-slate-100 font-bold text-slate-900 border-y border-slate-400">
                    <td class="p-2 border border-slate-300">TOTAL I - RESSOURCES STABLES (A+B+C+D)</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                </tr>

                {{-- SECTION II --}}
                <tr class="bg-slate-200 font-bold text-slate-900 border-t border-slate-300">
                    <td colspan="5" class="p-2 border border-slate-300 uppercase tracking-wide">II- EMPLOIS STABLES DE L'EXERCICE (FLUX)</td>
                </tr>
                @foreach([
                    'Acquisitions et augmentations d\'immobilisations (E)',
                    '+ Acquisitions d\'immobilisations incorporelles',
                    '+ Acquisitions d\'immobilisations corporelles',
                    '+ Acquisitions d\'immobilisations financières',
                    '+ Augmentation des créances immobilisées',
                    'Remboursement des capitaux propres (F)',
                    'Remboursements des dettes de financement (G)',
                    'Emplois en non valeurs (H)',
                ] as $ligne)
                <tr class="hover:bg-slate-50 border-b border-slate-200">
                    <td class="p-2 border border-slate-200 font-medium text-slate-600">{{ $ligne }}</td>
                    <td class="p-2 border border-slate-200 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-200 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-200 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-200 text-right font-mono">&nbsp;</td>
                </tr>
                @endforeach
                <tr class="bg-slate-100 font-bold text-slate-900 border-y border-slate-400">
                    <td class="p-2 border border-slate-300">TOTAL II - EMPLOIS STABLES (E+F+G+H)</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                </tr>

                <tr class="bg-slate-200 font-bold text-slate-900 border-t border-slate-300">
                    <td class="p-2 border border-slate-300 uppercase tracking-wide">III- VARIATION DU BESOIN DE FINANCEMENT GLOBAL (B.F.G)</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                </tr>
                <tr class="bg-slate-200 font-bold text-slate-900 border-t border-slate-300">
                    <td class="p-2 border border-slate-300 uppercase tracking-wide">IV- VARIATION DE LA TRESORERIE</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                </tr>
                <tr class="bg-slate-100 font-bold text-slate-900 border-y border-slate-400">
                    <td class="p-2 border border-slate-300 uppercase">TOTAL GENERAL</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                </tr>
            </tbody>
        </table>
    </div>

    <p class="text-[10px] text-slate-400 mt-2">(1) nettes de primes de remboursement</p>
</div>
@endsection
