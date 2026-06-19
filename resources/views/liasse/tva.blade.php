@extends('layouts.app')

@section('content')
<div class="container mx-auto p-6 bg-white shadow-md rounded-lg">
    <div class="flex justify-between items-center border-b pb-4 mb-6">
        <h2 class="text-xl font-bold text-gray-800 uppercase tracking-wide">Détail de la Taxe sur la Valeur Ajoutée</h2>
        <span class="text-sm font-semibold text-gray-600">D3 Soft SARL AU - Exercice : {{ $exercice }}</span>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full table-auto border-collapse border border-gray-400 text-sm">
            <thead>
                <tr class="bg-gray-100 text-center text-gray-800 font-bold">
                    <th class="border border-gray-400 p-2 w-2/5">Nature</th>
                    <th class="border border-gray-400 p-2 w-1/5">Solde au début de l'exercice<br><span class="italic font-normal">1</span></th>
                    <th class="border border-gray-400 p-2 w-1/5">Opérations comptables de l'exercice<br><span class="italic font-normal">2</span></th>
                    <th class="border border-gray-400 p-2 w-1/5">Déclarations TVA de l'exercice<br><span class="italic font-normal">3</span></th>
                    <th class="border border-gray-400 p-2 w-1/5">Solde fin d'exercice<br><span class="italic font-normal">(1 + 2 - 3 = 4)</span></th>
                </tr>
            </thead>
            <tbody>
                {{-- Ligne 1 --}}
                <tr class="hover:bg-gray-50 font-bold text-gray-900">
                    <td class="border border-gray-400 p-2 text-left">A. T.V.A. Facturée</td>
                    <td class="border border-gray-400 p-2 text-right font-mono">0,00</td>
                    <td class="border border-gray-400 p-2 text-right font-mono bg-yellow-50">120 708,12</td>
                    <td class="border border-gray-400 p-2 text-right font-mono bg-yellow-50">0,00</td>
                    <td class="border border-gray-400 p-2 text-right font-mono">120 708,12</td>
                </tr>
                
                {{-- Ligne 2 --}}
                <tr class="hover:bg-gray-50 font-bold text-gray-900">
                    <td class="border border-gray-400 p-2 text-left">B. T.V.A. Récupérable</td>
                    <td class="border border-gray-400 p-2 text-right font-mono">0,00</td>
                    <td class="border border-gray-400 p-2 text-right font-mono">17 274,63</td>
                    <td class="border border-gray-400 p-2 text-right font-mono">0,00</td>
                    <td class="border border-gray-400 p-2 text-right font-mono">17 274,63</td>
                </tr>

                {{-- Ligne 3 --}}
                <tr class="hover:bg-gray-50 text-gray-700">
                    <td class="border border-gray-400 p-2 pl-8 text-left">- sur charges</td>
                    <td class="border border-gray-400 p-2 text-right font-mono">0,00</td>
                    <td class="border border-gray-400 p-2 text-right font-mono bg-yellow-50">10 904,63</td>
                    <td class="border border-gray-400 p-2 text-right font-mono bg-yellow-50">0,00</td>
                    <td class="border border-gray-400 p-2 text-right font-mono">10 904,63</td>
                </tr>

                {{-- Ligne 4 --}}
                <tr class="hover:bg-gray-50 text-gray-700">
                    <td class="border border-gray-400 p-2 pl-8 text-left">- sur immobilisations</td>
                    <td class="border border-gray-400 p-2 text-right font-mono">0,00</td>
                    <td class="border border-gray-400 p-2 text-right font-mono bg-yellow-50">6 370,00</td>
                    <td class="border border-gray-400 p-2 text-right font-mono bg-yellow-50">0,00</td>
                    <td class="border border-gray-400 p-2 text-right font-mono">6 370,00</td>
                </tr>

                {{-- Ligne 5 --}}
                <tr class="hover:bg-gray-50 font-bold text-gray-900">
                    <td class="border border-gray-400 p-2 text-left">C. T.V.A. due ou crédit de T.V.A = (A - B)</td>
                    <td class="border border-gray-400 p-2 text-right font-mono">0,00</td>
                    <td class="border border-gray-400 p-2 text-right font-mono">103 433,49</td>
                    <td class="border border-gray-400 p-2 text-right font-mono">0,00</td>
                    <td class="border border-gray-400 p-2 text-right font-mono">103 433,49</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection