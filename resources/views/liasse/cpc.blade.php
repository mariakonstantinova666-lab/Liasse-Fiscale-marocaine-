@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <div class="bg-white p-4 shadow-sm rounded border">
        <h5 class="text-center mb-4 fw-bold">COMPTE DE PRODUITS ET CHARGES (HORS TAXES)</h5>
        
        <table class="table table-bordered align-middle table-sm border-dark">
            <thead class="table-light text-center fw-bold" style="font-size: 0.75rem;">
                <tr>
                    <th rowspan="2" style="width: 35%;">ÉLÉMENTS</th>
                    <th colspan="3">EXERCICE</th>
                    <th>PRÉCÉDENT</th>
                </tr>
                <tr>
                    <th style="width: 15%;">Propres (1)</th>
                    <th style="width: 15%;">Précédents (2)</th>
                    <th style="width: 15%;">Total (3=1+2)</th>
                    <th style="width: 15%;">Exercice (4)</th>
                </tr>
            </thead>
            <tbody style="font-size: 0.85rem;">
                @php
                    $calcTotal = function($section) {
                        $c1 = 0; $c2 = 0; $c4 = 0;
                        foreach($section as $v) { $c1 += $v->col1; $c2 += $v->col2; $c4 += $v->col4; }
                        return ['1' => $c1, '2' => $c2, '3' => $c1+$c2, '4' => $c4];
                    };

                    $tI = $calcTotal($cpcData['I. PRODUITS D\'EXPLOITATION']);
                    $tII = $calcTotal($cpcData['II. CHARGES D\'EXPLOITATION']);
                    $resIII = ['3' => $tI['3'] - $tII['3'], '4' => $tI['4'] - $tII['4']];
                    
                    $tIV = $calcTotal($cpcData['IV. PRODUITS FINANCIERS']);
                    $tV = $calcTotal($cpcData['V. CHARGES FINANCIERES']);
                    $resVI = ['3' => $tIV['3'] - $tV['3'], '4' => $tIV['4'] - $tV['4']];
                    
                    $resVII = ['3' => $resIII['3'] + $resVI['3'], '4' => $resIII['4'] + $resVI['4']];

                    $tVIII = $calcTotal($cpcData['VIII. PRODUITS NON COURANTS']);
                    $tIX = $calcTotal($cpcData['IX. CHARGES NON COURANTES']);
                    $resX = ['3' => $tVIII['3'] - $tIX['3'], '4' => $tVIII['4'] - $tIX['4']];

                    $resXI = ['3' => $resVII['3'] + $resX['3'], '4' => $resVII['4'] + $resX['4']];
                    $tXII = $calcTotal($cpcData['XII. IMPOTS SUR LES RÉSULTATS']);
                    $resXIII = ['3' => $resXI['3'] - $tXII['3'], '4' => $resXI['4'] - $tXII['4']];

                    $totXIV = $tI['3'] + $tIV['3'] + $tVIII['3'];
                    $totXV = $tII['3'] + $tV['3'] + $tIX['3'] + $tXII['3'];
                @endphp

                {{-- AFFICHAGE I & II --}}
                <tr class="fw-bold bg-light"><td>I. PRODUITS D'EXPLOITATION</td><td colspan="4"></td></tr>
                @foreach($cpcData['I. PRODUITS D\'EXPLOITATION'] as $label => $v)
                    <tr><td class="ps-4">{{ $label }}</td><td class="text-end">{{ number_format($v->col1, 2) }}</td><td class="text-end">{{ number_format($v->col2, 2) }}</td><td class="text-end fw-bold">{{ number_format($v->col1+$v->col2, 2) }}</td><td class="text-end">{{ number_format($v->col4, 2) }}</td></tr>
                @endforeach
                <tr class="fw-bold"><td>TOTAL I</td><td></td><td></td><td class="text-end">{{ number_format($tI['3'], 2) }}</td><td class="text-end">{{ number_format($tI['4'], 2) }}</td></tr>

                <tr class="fw-bold bg-light"><td>II. CHARGES D'EXPLOITATION</td><td colspan="4"></td></tr>
                @foreach($cpcData['II. CHARGES D\'EXPLOITATION'] as $label => $v)
                    <tr><td class="ps-4">{{ $label }}</td><td class="text-end">{{ number_format($v->col1, 2) }}</td><td class="text-end">{{ number_format($v->col2, 2) }}</td><td class="text-end fw-bold">{{ number_format($v->col1+$v->col2, 2) }}</td><td class="text-end">{{ number_format($v->col4, 2) }}</td></tr>
                @endforeach
                <tr class="fw-bold"><td>TOTAL II</td><td></td><td></td><td class="text-end">{{ number_format($tII['3'], 2) }}</td><td class="text-end">{{ number_format($tII['4'], 2) }}</td></tr>

                <tr class="fw-bold table-info"><td>III. RÉSULTAT D'EXPLOITATION (I - II)</td><td colspan="2" class="hachure"></td><td class="text-end">{{ number_format($resIII['3'], 2) }}</td><td class="text-end">{{ number_format($resIII['4'], 2) }}</td></tr>

                {{-- AFFICHAGE IV & V --}}
                <tr class="fw-bold bg-light"><td>IV. PRODUITS FINANCIERS</td><td colspan="4"></td></tr>
                <tr class="fw-bold bg-light"><td>V. CHARGES FINANCIERES</td><td colspan="4"></td></tr>
                <tr class="fw-bold"><td>VI. RÉSULTAT FINANCIER (IV - V)</td><td colspan="2" class="hachure"></td><td class="text-end">{{ number_format($resVI['3'], 2) }}</td><td class="text-end">{{ number_format($resVI['4'], 2) }}</td></tr>
                <tr class="fw-bold table-warning"><td>VII. RÉSULTAT COURANT (III + VI)</td><td colspan="2" class="hachure"></td><td class="text-end">{{ number_format($resVII['3'], 2) }}</td><td class="text-end">{{ number_format($resVII['4'], 2) }}</td></tr>

                {{-- AFFICHAGE VIII & IX (NON COURANT) --}}
                <tr class="fw-bold bg-light"><td>VIII. PRODUITS NON COURANTS</td><td colspan="4"></td></tr>
                @foreach($cpcData['VIII. PRODUITS NON COURANTS'] as $label => $v)
                    <tr><td class="ps-4">{{ $label }}</td><td class="text-end">{{ number_format($v->col1, 2) }}</td><td class="text-end">{{ number_format($v->col2, 2) }}</td><td class="text-end fw-bold">{{ number_format($v->col1+$v->col2, 2) }}</td><td class="text-end">{{ number_format($v->col4, 2) }}</td></tr>
                @endforeach
                <tr class="fw-bold bg-light"><td>IX. CHARGES NON COURANTES</td><td colspan="4"></td></tr>
                @foreach($cpcData['IX. CHARGES NON COURANTES'] as $label => $v)
                    <tr><td class="ps-4">{{ $label }}</td><td class="text-end">{{ number_format($v->col1, 2) }}</td><td class="text-end">{{ number_format($v->col2, 2) }}</td><td class="text-end fw-bold">{{ number_format($v->col1+$v->col2, 2) }}</td><td class="text-end">{{ number_format($v->col4, 2) }}</td></tr>
                @endforeach

                <tr class="fw-bold"><td>X. RÉSULTAT NON COURANT (VIII - IX)</td><td colspan="2" class="hachure"></td><td class="text-end">{{ number_format($resX['3'], 2) }}</td><td class="text-end">{{ number_format($resX['4'], 2) }}</td></tr>
                <tr class="fw-bold table-secondary"><td>XI. RÉSULTAT AVANT IMPOTS (VII + X)</td><td colspan="2" class="hachure"></td><td class="text-end">{{ number_format($resXI['3'], 2) }}</td><td class="text-end">{{ number_format($resXI['4'], 2) }}</td></tr>
                
                <tr><td>XII. IMPOTS SUR LES RÉSULTATS</td><td colspan="2" class="hachure"></td><td class="text-end">{{ number_format($tXII['3'], 2) }}</td><td class="text-end">{{ number_format($tXII['4'], 2) }}</td></tr>
                
                <tr class="fw-bold table-dark text-white"><td>XIII. RÉSULTAT NET (XI - XII)</td><td colspan="2" class="hachure"></td><td class="text-end">{{ number_format($resXIII['3'], 2) }}</td><td class="text-end">{{ number_format($resXIII['4'], 2) }}</td></tr>

                {{-- TOTAL DE SYNTHÈSE (Bas de l'image image_e26113.png) --}}
                <tr class="fw-bold border-top border-dark"><td>XIV. TOTAL DES PRODUITS (I+IV+VIII)</td><td colspan="2" class="hachure"></td><td class="text-end">{{ number_format($totXIV, 2) }}</td><td class="text-end">0,00</td></tr>
                <tr class="fw-bold"><td>XV. TOTAL DES CHARGES (II+V+IX+XII)</td><td colspan="2" class="hachure"></td><td class="text-end">{{ number_format($totXV, 2) }}</td><td class="text-end">0,00</td></tr>
                <tr class="fw-bold table-primary"><td>XVI. RÉSULTAT NET (XIV - XV)</td><td colspan="2" class="hachure"></td><td class="text-end">{{ number_format($totXIV - $totXV, 2) }}</td><td class="text-end">0,00</td></tr>
            </tbody>
        </table>
    </div>
</div>

<style>
    .hachure { background: repeating-linear-gradient(45deg, #eee, #eee 10px, #ddd 10px, #ddd 20px); }
    .table td, .table th { border: 1px solid black !important; }
</style>
@endsection