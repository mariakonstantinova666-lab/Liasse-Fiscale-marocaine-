@extends('layouts.app')

@section('content')
<div class="container mx-auto p-6 bg-white shadow-md rounded-lg">
    <div class="flex justify-between items-center border-b pb-4 mb-6">
        <h2 class="text-xl font-bold text-gray-800 uppercase tracking-wide">Tableau T12 — DETAIL DE LA TAXE SUR LA VALEUR AJOUTEE</h2>
        <span class="text-sm font-semibold text-gray-600">Exercice : {{ $exercice }}</span>
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
