@extends('layouts.app')

@section('content')
<div class="bg-white shadow-lg rounded-sm border border-slate-200 p-6">
    <form method="POST" action="{{ route('liasse.save', 'plus_values_fusion') }}">
        @csrf
    <div class="flex justify-between items-center mb-4 border-b pb-2">
        <div>
            <h2 class="text-xl font-bold text-slate-800 uppercase tracking-wider">
                ÉTAT DES PLUS-VALUES CONSTATÉES EN CAS DE FUSION
            </h2>
            <p class="text-sm text-slate-500 mt-1">Exercice : <strong>{{ $exercice ?? session('annee_exercice', 2025) }}</strong></p>
        </div>
        <span class="text-sm font-semibold bg-slate-100 px-3 py-1 rounded text-slate-600">Tableau N° 17 — Exercice {{ $exercice ?? session('annee_exercice', 2025) }}</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-xs text-left border-collapse border border-slate-300" style="min-width: 1400px;">
            <thead class="text-white text-center font-bold">
                <tr>
                    <th class="p-2 border border-slate-300 bg-slate-800 align-middle" style="width: 18%;">Eléments</th>
                    <th class="p-2 border border-slate-300 bg-slate-800 align-middle">Valeur d'apport</th>
                    <th class="p-2 border border-slate-300 bg-slate-800 align-middle">Valeur nette comptable</th>
                    <th class="p-2 border border-slate-300 bg-slate-800 align-middle">Plus-value constatée et différée</th>
                    <th class="p-2 border border-slate-300 bg-slate-800 align-middle">Fraction de la plus-value rapportée aux exercices antérieurs (cumul) (2)</th>
                    <th class="p-2 border border-slate-300 bg-slate-800 align-middle">Fraction de la plus-value rapportée à l'exercice actuel</th>
                    <th class="p-2 border border-slate-300 bg-slate-800 align-middle">Cumul des plus-values rapportées</th>
                    <th class="p-2 border border-slate-300 bg-slate-800 align-middle">Solde des plus-values non imputées</th>
                    <th class="p-2 border border-slate-300 bg-slate-800 align-middle">Observations</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $lignesFusion = [
                        '1' => 'Terrain (1)',
                        '2' => 'Constructions',
                        '3' => 'Matériel et outillage',
                        '4' => 'Matériel de transport',
                        '5' => 'Agencements-Installations',
                        '6' => 'Brevets',
                        '7' => 'Autres éléments amortissables',
                        '8' => 'Titres de participation',
                        '9' => 'Fonds de commerce',
                        '10' => 'Autres éléments non amortissables',
                    ];
                @endphp
                @foreach($lignesFusion as $num => $libelle)
                    <tr class="hover:bg-slate-50 border-b border-slate-200">
                        <td class="p-2 border border-slate-200 pl-4 font-medium text-slate-600">{{ $num }}&nbsp;&nbsp;{{ $libelle }}</td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[r{{ $loop->index }}_c2]" value="{{ $data['r'.$loop->index.'_c2'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[r{{ $loop->index }}_c3]" value="{{ $data['r'.$loop->index.'_c3'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[r{{ $loop->index }}_c4]" value="{{ $data['r'.$loop->index.'_c4'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[r{{ $loop->index }}_c5]" value="{{ $data['r'.$loop->index.'_c5'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[r{{ $loop->index }}_c6]" value="{{ $data['r'.$loop->index.'_c6'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[r{{ $loop->index }}_c7]" value="{{ $data['r'.$loop->index.'_c7'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[r{{ $loop->index }}_c8]" value="{{ $data['r'.$loop->index.'_c8'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[r{{ $loop->index }}_c9]" value="{{ $data['r'.$loop->index.'_c9'] ?? '' }}" class="w-full bg-transparent text-left font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    </tr>
                @endforeach

                <tr class="bg-slate-100 font-bold text-slate-900 border-y border-slate-400">
                    <td class="p-2 border border-slate-300 uppercase">Total</td>
                    <td class="p-1 border border-slate-300"><input type="text" name="f[total_c2]" value="{{ $data['total_c2'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-300"><input type="text" name="f[total_c3]" value="{{ $data['total_c3'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-300"><input type="text" name="f[total_c4]" value="{{ $data['total_c4'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-300"><input type="text" name="f[total_c5]" value="{{ $data['total_c5'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-300"><input type="text" name="f[total_c6]" value="{{ $data['total_c6'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-300"><input type="text" name="f[total_c7]" value="{{ $data['total_c7'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-300"><input type="text" name="f[total_c8]" value="{{ $data['total_c8'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-300"><input type="text" name="f[total_c9]" value="{{ $data['total_c9'] ?? '' }}" class="w-full bg-transparent text-left font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="mt-3 space-y-1">
        <p class="text-[10px] text-slate-400">(1) Imposition différée jusqu'à la date de cession.</p>
        <p class="text-[10px] text-slate-400">(2) Fractions correspondant à l'imputation normale de la plus-value constatée lors de la fusion majorée le cas échéant du reliquat de la plus-value se rapportant à l'élément cédé au cours de l'exercice.</p>
    </div>
        <div class="mt-4 flex justify-end"><button type="submit" class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">💾 Enregistrer le tableau</button></div>
    </form>
</div>
@endsection
