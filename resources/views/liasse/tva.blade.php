@extends('layouts.app')

@section('content')
<div class="container mx-auto p-6 bg-white shadow-md rounded-lg">
    <div class="liasse-page-header">
        <div class="liasse-page-heading">
            <h2 class="liasse-page-title">Tableau T12 — DETAIL DE LA TAXE SUR LA VALEUR AJOUTEE</h2>
            <div class="liasse-page-meta">
                <span class="liasse-page-meta-item liasse-page-meta-exercise">Exercice : <strong>{{ $exercice }}</strong></span>
                <span class="liasse-page-meta-item liasse-page-meta-closing">Clôture : <strong>31/12/{{ $exercice }}</strong></span>
            </div>
        </div>
        <span class="liasse-page-badge">Tableau T12</span>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full table-auto border-collapse border border-gray-400 text-sm">
            <thead>
                <tr class="bg-gray-100 text-center text-gray-800 font-bold">
                    <th class="border border-gray-400 p-2 w-2/5">Nature</th>
                    <th class="border border-gray-400 p-2">Solde au début de l'exercice<br><span class="font-normal">1</span></th>
                    <th class="border border-gray-400 p-2">Opérations comptables de l'exercice<br><span class="font-normal">2</span></th>
                    <th class="border border-gray-400 p-2">Déclarations TVA de l'exercice<br><span class="font-normal">3</span></th>
                    <th class="border border-gray-400 p-2">Solde fin d'exercice<br><span class="font-normal">1 + 2 - 3 = 4</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach($tvaRows as $row)
                    @php($v = $row['values'])
                    <tr class="hover:bg-gray-50 {{ !empty($row['bold']) ? 'font-bold text-gray-900' : 'text-gray-700' }}">
                        <td class="border border-gray-400 p-2 text-left {{ empty($row['bold']) ? 'pl-8' : '' }}">{{ $row['label'] }}</td>
                        <td class="border border-gray-400 p-2 text-right font-mono">{{ number_format($v->debut, 2, ',', ' ') }}</td>
                        <td class="border border-gray-400 p-2 text-right font-mono">{{ number_format($v->operations, 2, ',', ' ') }}</td>
                        <td class="border border-gray-400 p-2 text-right font-mono">{{ number_format($v->declarations, 2, ',', ' ') }}</td>
                        <td class="border border-gray-400 p-2 text-right font-mono">{{ number_format($v->fin, 2, ',', ' ') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="mt-3 text-xs text-gray-500">Les déclarations ne sont pas déductibles d'une balance de clôture et restent à zéro tant qu'aucune source déclarative n'est fournie.</p>
</div>
@endsection
