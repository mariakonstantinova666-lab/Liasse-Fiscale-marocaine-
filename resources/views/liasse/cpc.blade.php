@extends('layouts.app')

@section('content')
<div class="cpc-excel bg-white shadow-lg rounded-sm border border-slate-200 p-6">
    <div class="flex justify-between items-center mb-4 border-b pb-2">
        <div>
            <h2 class="text-xl font-bold text-slate-800 uppercase tracking-wider">Tableau T02 — COMPTE DE PRODUITS ET CHARGES ( HORS TAXES )</h2>
            <p class="text-sm text-slate-500 mt-1">Hors taxes — Exercice : <strong>{{ $exercice }}</strong></p>
        </div>
        <span class="text-sm font-semibold bg-slate-100 px-3 py-1 rounded text-slate-600">Tableau T02</span>
    </div>

    <div>
        <h5 class="text-center mb-3 fw-bold text-slate-900 uppercase tracking-wide">COMPTE DE PRODUITS ET CHARGES ( HORS TAXES )</h5>

        @php
            $fmt = fn ($value) => number_format((float) $value, 2, ',', ' ');

            $blankRow = fn () => (object) [
                'col1' => 0,
                'col2' => 0,
                'col3' => 0,
                'col4' => 0,
            ];

            $sectionRows = function (string $prefix) use ($cpcData) {
                foreach ($cpcData as $section => $rows) {
                    if (str_starts_with($section, $prefix)) {
                        return array_values($rows);
                    }
                }

                return [];
            };

            $pick = function (array $rows, int $index) use ($blankRow) {
                return $rows[$index] ?? $blankRow();
            };

            $row = function (string $sectionPrefix, int $index) use ($sectionRows, $pick) {
                return $pick($sectionRows($sectionPrefix), $index);
            };

            $singleSectionRow = function (string $sectionPrefix) use ($sectionRows, $blankRow) {
                $rows = $sectionRows($sectionPrefix);

                return $rows[0] ?? $blankRow();
            };

            $sumRows = function (array $rows) {
                $total = ['col1' => 0, 'col2' => 0, 'col3' => 0, 'col4' => 0];

                foreach ($rows as $item) {
                    $total['col1'] += (float) $item->col1;
                    $total['col2'] += (float) $item->col2;
                    $total['col3'] += (float) $item->col3;
                    $total['col4'] += (float) $item->col4;
                }

                return (object) $total;
            };

            $produitsExploitation = [
                $row('I.', 0),
                $row('I.', 1),
                $row('I.', 2),
                $row('I.', 3),
                $row('I.', 4),
                $row('I.', 5),
                $row('I.', 6),
            ];

            $ventesMarchandises = $produitsExploitation[0];
            $ventesBiensServices = $produitsExploitation[1];
            $chiffresAffaires = $sumRows([$ventesMarchandises, $ventesBiensServices]);
            $totalI = $sumRows([$chiffresAffaires, ...array_slice($produitsExploitation, 2)]);

            $chargesExploitation = [
                $row('II.', 0),
                $row('II.', 1),
                $row('II.', 2),
                $row('II.', 3),
                $row('II.', 4),
                $row('II.', 5),
                $row('II.', 6),
            ];
            $totalII = $sumRows($chargesExploitation);

            $resultatIII = (object) [
                'col1' => 0,
                'col2' => 0,
                'col3' => $totalI->col3 - $totalII->col3,
                'col4' => $totalI->col4 - $totalII->col4,
            ];

            $produitsFinanciers = [
                $row('IV.', 0),
                $row('IV.', 1),
                $row('IV.', 2),
                $row('IV.', 3),
            ];
            $totalIV = $sumRows($produitsFinanciers);

            $chargesFinancieres = [
                $row('V.', 0),
                $row('V.', 1),
                $row('V.', 2),
                $row('V.', 3),
            ];
            $totalV = $sumRows($chargesFinancieres);

            $resultatVI = (object) [
                'col1' => 0,
                'col2' => 0,
                'col3' => $totalIV->col3 - $totalV->col3,
                'col4' => $totalIV->col4 - $totalV->col4,
            ];

            $resultatVII = (object) [
                'col1' => 0,
                'col2' => 0,
                'col3' => $resultatIII->col3 + $resultatVI->col3,
                'col4' => $resultatIII->col4 + $resultatVI->col4,
            ];

            $produitsNonCourants = [
                $row('VIII.', 0),
                $row('VIII.', 1),
                $row('VIII.', 2),
                $row('VIII.', 3),
                $row('VIII.', 4),
            ];
            $totalVIII = $sumRows($produitsNonCourants);

            $chargesNonCourantes = [
                $row('IX.', 0),
                $row('IX.', 1),
                $row('IX.', 2),
                $row('IX.', 3),
            ];
            $totalIX = $sumRows($chargesNonCourantes);

            $resultatX = (object) [
                'col1' => 0,
                'col2' => 0,
                'col3' => $totalVIII->col3 - $totalIX->col3,
                'col4' => $totalVIII->col4 - $totalIX->col4,
            ];

            $resultatXI = (object) [
                'col1' => 0,
                'col2' => 0,
                'col3' => $resultatVII->col3 + $resultatX->col3,
                'col4' => $resultatVII->col4 + $resultatX->col4,
            ];

            $totalXII = $singleSectionRow('XII.');

            $resultatXIII = (object) [
                'col1' => 0,
                'col2' => 0,
                'col3' => $resultatXI->col3 - $totalXII->col3,
                'col4' => $resultatXI->col4 - $totalXII->col4,
            ];

            $totalXIV = (object) [
                'col1' => 0,
                'col2' => 0,
                'col3' => $totalI->col3 + $totalIV->col3 + $totalVIII->col3,
                'col4' => $totalI->col4 + $totalIV->col4 + $totalVIII->col4,
            ];

            $totalXV = (object) [
                'col1' => 0,
                'col2' => 0,
                'col3' => $totalII->col3 + $totalV->col3 + $totalIX->col3 + $totalXII->col3,
                'col4' => $totalII->col4 + $totalV->col4 + $totalIX->col4 + $totalXII->col4,
            ];

            $resultatXVI = (object) [
                'col1' => 0,
                'col2' => 0,
                'col3' => $totalXIV->col3 - $totalXV->col3,
                'col4' => $totalXIV->col4 - $totalXV->col4,
            ];

            $amountCells = function ($values, bool $strong = false) use ($fmt) {
                $class = 'text-end text-nowrap' . ($strong ? ' fw-bold' : '');

                return '
                    <td class="'.$class.'">'.$fmt($values->col1).'</td>
                    <td class="'.$class.'">'.$fmt($values->col2).'</td>
                    <td class="'.$class.'">'.$fmt($values->col3).'</td>
                    <td class="'.$class.'">'.$fmt($values->col4).'</td>
                ';
            };
        @endphp

        <div class="table-responsive">
            <table class="w-full text-xs border-collapse border border-slate-300 cpc-table">
                <thead class="text-center font-bold">
                    <tr>
                        <th colspan="3" class="cpc-head-main">Eléments</th>
                        <th colspan="2" class="cpc-head-main">Opérations</th>
                        <th class="cpc-head-main">Totaux de l'exercice</th>
                        <th class="cpc-head-main">Exercice précédent</th>
                    </tr>
                    <tr>
                        <th colspan="3" class="cpc-head-sub"></th>
                        <th class="cpc-head-sub">Propres à l'exercice</th>
                        <th class="cpc-head-sub">Concernant les exercices précédents</th>
                        <th class="cpc-head-sub"></th>
                        <th class="cpc-head-sub"></th>
                    </tr>
                    <tr>
                        <th class="section-col cpc-head-index"></th>
                        <th class="num-col cpc-head-index"></th>
                        <th class="label-col cpc-head-index"></th>
                        <th class="cpc-head-index">1</th>
                        <th class="cpc-head-index">2</th>
                        <th class="cpc-head-index">3 = 1 + 2</th>
                        <th class="cpc-head-index">4</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="fw-bold section-row">
                        <td>EXPLOITATION</td>
                        <td class="text-center">I</td>
                        <td>PRODUITS D'EXPLOITATION</td>
                        {!! $amountCells($totalI, true) !!}
                    </tr>
                    <tr>
                        <td></td><td></td><td class="ps-4">Ventes de marchandises</td>
                        {!! $amountCells($ventesMarchandises) !!}
                    </tr>
                    <tr>
                        <td></td><td></td><td class="ps-4">Ventes de biens et services produits</td>
                        {!! $amountCells($ventesBiensServices) !!}
                    </tr>
                    <tr class="fw-semibold">
                        <td></td><td></td><td class="ps-4">Chiffres d'affaires</td>
                        {!! $amountCells($chiffresAffaires, true) !!}
                    </tr>
                    <tr>
                        <td></td><td></td><td class="ps-4">Variation de stock de produits</td>
                        {!! $amountCells($produitsExploitation[2]) !!}
                    </tr>
                    <tr>
                        <td></td><td></td><td class="ps-4">Immobilisations produites pour l'Ese p/elle même</td>
                        {!! $amountCells($produitsExploitation[3]) !!}
                    </tr>
                    <tr>
                        <td></td><td></td><td class="ps-4">Subvention d'exploitation</td>
                        {!! $amountCells($produitsExploitation[4]) !!}
                    </tr>
                    <tr>
                        <td></td><td></td><td class="ps-4">Autres produits d'exploitation</td>
                        {!! $amountCells($produitsExploitation[5]) !!}
                    </tr>
                    <tr>
                        <td></td><td></td><td class="ps-4">Reprises d'exploitation; transfert de charges</td>
                        {!! $amountCells($produitsExploitation[6]) !!}
                    </tr>
                    <tr class="fw-bold total-row">
                        <td></td><td></td><td class="ps-4">TOTAL I</td>
                        {!! $amountCells($totalI, true) !!}
                    </tr>

                    <tr class="fw-bold section-row">
                        <td></td>
                        <td class="text-center">II</td>
                        <td>CHARGES D'EXPLOITATION</td>
                        {!! $amountCells($totalII, true) !!}
                    </tr>
                    <tr>
                        <td></td><td></td><td class="ps-4">Achats revendus de marchandises</td>
                        {!! $amountCells($chargesExploitation[0]) !!}
                    </tr>
                    <tr>
                        <td></td><td></td><td class="ps-4">Achat consommes de matières et de fournitures</td>
                        {!! $amountCells($chargesExploitation[1]) !!}
                    </tr>
                    <tr>
                        <td></td><td></td><td class="ps-4">Autres charges externes</td>
                        {!! $amountCells($chargesExploitation[2]) !!}
                    </tr>
                    <tr>
                        <td></td><td></td><td class="ps-4">Impôts et taxes</td>
                        {!! $amountCells($chargesExploitation[3]) !!}
                    </tr>
                    <tr>
                        <td></td><td></td><td class="ps-4">Charges de personnel</td>
                        {!! $amountCells($chargesExploitation[4]) !!}
                    </tr>
                    <tr>
                        <td></td><td></td><td class="ps-4">Autres charges d'exploitation</td>
                        {!! $amountCells($chargesExploitation[5]) !!}
                    </tr>
                    <tr>
                        <td></td><td></td><td class="ps-4">Dotations d'exploitation</td>
                        {!! $amountCells($chargesExploitation[6]) !!}
                    </tr>
                    <tr class="fw-bold total-row">
                        <td></td><td></td><td class="ps-4">TOTAL II</td>
                        {!! $amountCells($totalII, true) !!}
                    </tr>
                    <tr class="fw-bold result-row">
                        <td></td><td class="text-center">III</td><td>RESULTAT D'EXPLOITATION ( I - II )</td>
                        {!! $amountCells($resultatIII, true) !!}
                    </tr>

                    <tr class="fw-bold section-row">
                        <td>FINANCIER</td>
                        <td class="text-center">IV</td>
                        <td>PRODUITS FINANCIERS</td>
                        {!! $amountCells($totalIV, true) !!}
                    </tr>
                    <tr>
                        <td></td><td></td><td class="ps-4">Produits des titres de partic. et autres titres immo.</td>
                        {!! $amountCells($produitsFinanciers[0]) !!}
                    </tr>
                    <tr>
                        <td></td><td></td><td class="ps-4">Gains de change</td>
                        {!! $amountCells($produitsFinanciers[1]) !!}
                    </tr>
                    <tr>
                        <td></td><td></td><td class="ps-4">Intérêts et autres produits financiers</td>
                        {!! $amountCells($produitsFinanciers[2]) !!}
                    </tr>
                    <tr>
                        <td></td><td></td><td class="ps-4">Reprises financières; transfert de charges</td>
                        {!! $amountCells($produitsFinanciers[3]) !!}
                    </tr>
                    <tr class="fw-bold total-row">
                        <td></td><td></td><td class="ps-4">TOTAL IV</td>
                        {!! $amountCells($totalIV, true) !!}
                    </tr>

                    <tr class="fw-bold section-row">
                        <td></td>
                        <td class="text-center">V</td>
                        <td>CHARGES FINANCIERES</td>
                        {!! $amountCells($totalV, true) !!}
                    </tr>
                    <tr>
                        <td></td><td></td><td class="ps-4">Charges d'intérêts</td>
                        {!! $amountCells($chargesFinancieres[0]) !!}
                    </tr>
                    <tr>
                        <td></td><td></td><td class="ps-4">Pertes de changes</td>
                        {!! $amountCells($chargesFinancieres[1]) !!}
                    </tr>
                    <tr>
                        <td></td><td></td><td class="ps-4">Autres charges financières</td>
                        {!! $amountCells($chargesFinancieres[2]) !!}
                    </tr>
                    <tr>
                        <td></td><td></td><td class="ps-4">Dotations financières</td>
                        {!! $amountCells($chargesFinancieres[3]) !!}
                    </tr>
                    <tr class="fw-bold total-row">
                        <td></td><td></td><td class="ps-4">TOTAL V</td>
                        {!! $amountCells($totalV, true) !!}
                    </tr>
                    <tr class="fw-bold result-row">
                        <td></td><td class="text-center">VI</td><td>RESULTAT FINANCIER ( IV - V )</td>
                        {!! $amountCells($resultatVI, true) !!}
                    </tr>
                    <tr class="fw-bold result-row">
                        <td></td><td class="text-center">VII</td><td>RESULTAT COURANT ( III - V I)</td>
                        {!! $amountCells($resultatVII, true) !!}
                    </tr>
                    <tr class="note-row">
                        <td colspan="7">(1) Variation de stocks : stocks final - stocks initial ;augmentation (+) ;diminution (-)</td>
                    </tr>
                    <tr class="note-row">
                        <td colspan="7">(2) Achats revendus ou consommes : achats - variation de stocks.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h5 class="text-center my-3 fw-bold text-slate-900 uppercase tracking-wide">COMPTE DE PRODUITS ET CHARGES ( HORS TAXES ) (Suite)</h5>

        <div class="table-responsive">
            <table class="w-full text-xs border-collapse border border-slate-300 cpc-table">
                <thead class="text-center font-bold">
                    <tr>
                        <th colspan="3" class="cpc-head-main">Eléments</th>
                        <th colspan="2" class="cpc-head-main">Opérations</th>
                        <th class="cpc-head-main">Totaux de l'exercice</th>
                        <th class="cpc-head-main">Exercice précédent</th>
                    </tr>
                    <tr>
                        <th colspan="3" class="cpc-head-sub"></th>
                        <th class="cpc-head-sub">Propres à l'exercice</th>
                        <th class="cpc-head-sub">Concernant les exercices précédents</th>
                        <th class="cpc-head-sub"></th>
                        <th class="cpc-head-sub"></th>
                    </tr>
                    <tr>
                        <th class="section-col cpc-head-index"></th>
                        <th class="num-col cpc-head-index"></th>
                        <th class="label-col cpc-head-index"></th>
                        <th class="cpc-head-index">1</th>
                        <th class="cpc-head-index">2</th>
                        <th class="cpc-head-index">3 = 1 + 2</th>
                        <th class="cpc-head-index">4</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="fw-bold result-row">
                        <td></td><td class="text-center">VII</td><td>RESULTAT COURANT ( Report )</td>
                        {!! $amountCells($resultatVII, true) !!}
                    </tr>

                    <tr class="fw-bold section-row">
                        <td>NON COURANT</td>
                        <td class="text-center">VIII</td>
                        <td>PRODUITS NON COURANTS</td>
                        {!! $amountCells($totalVIII, true) !!}
                    </tr>
                    <tr>
                        <td></td><td></td><td class="ps-4">Produits des cessions d'immobilisations</td>
                        {!! $amountCells($produitsNonCourants[0]) !!}
                    </tr>
                    <tr>
                        <td></td><td></td><td class="ps-4">Subventions d'équilibre</td>
                        {!! $amountCells($produitsNonCourants[1]) !!}
                    </tr>
                    <tr>
                        <td></td><td></td><td class="ps-4">Reprises sur subventions d'investissement</td>
                        {!! $amountCells($produitsNonCourants[2]) !!}
                    </tr>
                    <tr>
                        <td></td><td></td><td class="ps-4">Autres produits non courants</td>
                        {!! $amountCells($produitsNonCourants[3]) !!}
                    </tr>
                    <tr>
                        <td></td><td></td><td class="ps-4">Reprises non courantes; transferts de charges</td>
                        {!! $amountCells($produitsNonCourants[4]) !!}
                    </tr>
                    <tr class="fw-bold total-row">
                        <td></td><td></td><td class="ps-4">TOTAL VIII</td>
                        {!! $amountCells($totalVIII, true) !!}
                    </tr>

                    <tr class="fw-bold section-row">
                        <td></td>
                        <td class="text-center">IX</td>
                        <td>CHARGES NON COURANTES</td>
                        {!! $amountCells($totalIX, true) !!}
                    </tr>
                    <tr>
                        <td></td><td></td><td class="ps-4">Valeurs nettes d'amortis. des immos cédées</td>
                        {!! $amountCells($chargesNonCourantes[0]) !!}
                    </tr>
                    <tr>
                        <td></td><td></td><td class="ps-4">Subventions accordées</td>
                        {!! $amountCells($chargesNonCourantes[1]) !!}
                    </tr>
                    <tr>
                        <td></td><td></td><td class="ps-4">Autres charges non courantes</td>
                        {!! $amountCells($chargesNonCourantes[2]) !!}
                    </tr>
                    <tr>
                        <td></td><td></td><td class="ps-4">Dotations non courantes aux amortiss. et prov.</td>
                        {!! $amountCells($chargesNonCourantes[3]) !!}
                    </tr>
                    <tr class="fw-bold total-row">
                        <td></td><td></td><td class="ps-4">TOTAL IX</td>
                        {!! $amountCells($totalIX, true) !!}
                    </tr>
                    <tr class="fw-bold result-row">
                        <td></td><td class="text-center">X</td><td>RESULTAT NON COURANT ( VIII- IV )</td>
                        {!! $amountCells($resultatX, true) !!}
                    </tr>
                    <tr class="fw-bold result-row">
                        <td></td><td class="text-center">XI</td><td>RESULTAT AVANT IMPOTS ( VII+ X )</td>
                        {!! $amountCells($resultatXI, true) !!}
                    </tr>
                    <tr>
                        <td></td><td class="text-center">XII</td><td>IMPOTS SUR LES RESULTATS</td>
                        {!! $amountCells($totalXII) !!}
                    </tr>
                    <tr class="fw-bold result-row">
                        <td></td><td class="text-center">XIII</td><td>RESULTAT NET ( XI - XII )</td>
                        {!! $amountCells($resultatXIII, true) !!}
                    </tr>
                    <tr class="spacer-row">
                        <td colspan="7"></td>
                    </tr>
                    <tr class="fw-bold total-row">
                        <td></td><td class="text-center">XIV</td><td>TOTAL DES PRODUITS ( I + IV + VIII )</td>
                        {!! $amountCells($totalXIV, true) !!}
                    </tr>
                    <tr class="fw-bold total-row">
                        <td></td><td class="text-center">XV</td><td>TOTAL DES CHARGES ( II + V + IX + XII )</td>
                        {!! $amountCells($totalXV, true) !!}
                    </tr>
                    <tr class="fw-bold total-row">
                        <td></td><td class="text-center">XVI</td><td>RESULTAT NET ( XIV - XV )</td>
                        {!! $amountCells($resultatXVI, true) !!}
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .cpc-excel .cpc-table {
        min-width: 1120px;
        font-size: 0.75rem;
        color: #334155;
        border-color: #cbd5e1 !important;
        font-family: inherit;
    }

    .cpc-excel .cpc-table th,
    .cpc-excel .cpc-table td {
        border: 1px solid #cbd5e1 !important;
        padding: 0.5rem;
        vertical-align: middle;
        line-height: 1.25rem;
    }

    .cpc-excel .cpc-table thead th {
        color: #ffffff !important;
        font-weight: 700;
        border-color: #334155 !important;
        text-transform: uppercase;
        letter-spacing: 0.02em;
        text-align: center;
    }

    .cpc-excel .cpc-head-main {
        background: #1e293b !important;
        color: #ffffff !important;
        font-size: 0.78rem;
    }

    .cpc-excel .cpc-head-sub {
        background: #334155 !important;
        color: #f8fafc !important;
        font-size: 0.72rem;
    }

    .cpc-excel .cpc-head-index {
        background: #475569 !important;
        color: #f8fafc !important;
        font-size: 0.72rem;
        font-weight: 700;
    }

    .cpc-excel .cpc-table tbody tr:hover td {
        background-color: #f8fafc;
    }

    .cpc-excel .section-col {
        width: 12%;
    }

    .cpc-excel .num-col {
        width: 5%;
    }

    .cpc-excel .label-col {
        width: 35%;
    }

    .cpc-excel .section-row td {
        background: #e2e8f0;
        color: #0f172a;
        border-color: #cbd5e1 !important;
        font-weight: 700;
    }

    .cpc-excel .total-row td {
        background: #f8fafc;
        color: #0f172a;
        font-weight: 700;
    }

    .cpc-excel .result-row td {
        background: #f1f5f9;
        color: #0f172a;
        border-top-color: #94a3b8 !important;
        border-bottom-color: #94a3b8 !important;
        font-weight: 700;
    }

    .cpc-excel .note-row td {
        border-left-color: transparent !important;
        border-right-color: transparent !important;
        font-size: 0.75rem;
        font-style: italic;
        color: #64748b;
    }

    .cpc-excel .spacer-row td {
        height: 1.25rem;
        border-left-color: transparent !important;
        border-right-color: transparent !important;
    }

    .cpc-excel .text-nowrap {
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
    }

    .dark .cpc-excel {
        background: #0f172a;
        border-color: #1e293b;
        color: #cbd5e1;
    }

    .dark .cpc-excel .cpc-table {
        color: #cbd5e1;
        border-color: #475569 !important;
    }

    .dark .cpc-excel .cpc-table th,
    .dark .cpc-excel .cpc-table td {
        border-color: #475569 !important;
    }

    .dark .cpc-excel .cpc-head-main {
        background: #1e293b !important;
        color: #f8fafc !important;
    }

    .dark .cpc-excel .cpc-head-sub {
        background: #1e293b !important;
        color: #f1f5f9 !important;
    }

    .dark .cpc-excel .cpc-head-index {
        background: #1e293b !important;
        color: #f8fafc !important;
    }

    .dark .cpc-excel .cpc-table tbody tr:hover td {
        background: rgba(99, 102, 241, 0.1);
    }

    .dark .cpc-excel .section-row td {
        background: #1e293b !important;
        color: #f8fafc !important;
        border-color: #475569 !important;
    }

    .dark .cpc-excel .total-row td {
        background: #1e293b !important;
        color: #f1f5f9 !important;
    }

    .dark .cpc-excel .result-row td {
        background: #1e293b !important;
        color: #ffffff !important;
        border-top-color: #64748b !important;
        border-bottom-color: #64748b !important;
    }

    .dark .cpc-excel .note-row td {
        background: #0f172a !important;
        color: #94a3b8 !important;
    }

    .dark .cpc-excel .spacer-row td {
        background: #0f172a !important;
    }
</style>
@endsection
