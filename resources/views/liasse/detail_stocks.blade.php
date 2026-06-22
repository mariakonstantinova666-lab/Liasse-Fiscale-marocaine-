@extends('layouts.app')

@section('content')
<div class="bg-white shadow-lg rounded-sm border border-slate-200 p-6">
    <form method="POST" action="{{ route('liasse.save', 'detail_stocks') }}">
    @csrf
    <div class="flex justify-between items-center mb-4 border-b pb-2">
        <div>
            <h2 class="text-xl font-bold text-slate-800 uppercase tracking-wider">
                État détaillé des stocks
            </h2>
            <p class="text-sm text-slate-500 mt-1">Exercice : <strong>{{ $exercice ?? session('annee_exercice', 2025) }}</strong></p>
        </div>
        <span class="text-sm font-semibold bg-slate-100 px-3 py-1 rounded text-slate-600">Tableau N° 20 — Exercice {{ $exercice ?? session('annee_exercice', 2025) }}</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-xs text-left border-collapse border border-slate-300" style="min-width: 1100px;">
            <thead class="text-white text-center font-bold">
                <tr>
                    <th rowspan="2" class="p-2 border border-slate-300 align-middle bg-slate-800 text-left" style="width: 28%;">STOCKS</th>
                    <th colspan="3" class="p-2 border border-slate-300 bg-slate-800">Stock Final</th>
                    <th colspan="3" class="p-2 border border-slate-300 bg-slate-800">Stock Initial</th>
                    <th rowspan="2" class="p-2 border border-slate-300 align-middle bg-slate-800">Variation de stock en Valeur (+ ou -)</th>
                </tr>
                <tr>
                    <th class="p-2 border border-slate-300 bg-slate-800 font-medium">Montant brut</th>
                    <th class="p-2 border border-slate-300 bg-slate-800 font-medium">Provision pour dépréciation</th>
                    <th class="p-2 border border-slate-300 bg-slate-800 font-medium">Montant net</th>
                    <th class="p-2 border border-slate-300 bg-slate-800 font-medium">Montant brut</th>
                    <th class="p-2 border border-slate-300 bg-slate-800 font-medium">Provision pour dépréciation</th>
                    <th class="p-2 border border-slate-300 bg-slate-800 font-medium">Montant net</th>
                </tr>
            </thead>
            <tbody>
                {{-- SECTION I --}}
                <tr class="bg-slate-200 font-bold text-slate-900 border-t border-slate-300 uppercase">
                    <td colspan="8" class="p-2 border border-slate-300 tracking-wide">I. Stocks Approvisionnement</td>
                </tr>

                <tr class="bg-slate-50 font-semibold text-slate-700">
                    <td colspan="8" class="p-2 border border-slate-200 pl-4 italic">Biens et produits destinés à la revente en l'état :</td>
                </tr>
                @foreach(['Biens immeubles', 'Biens meubles'] as $label)
                    @php($key = 's1a_'.$loop->index)
                    <tr class="hover:bg-slate-50 border-b border-slate-200">
                        <td class="p-2 border border-slate-200 pl-8 font-medium text-slate-600">{{ $label }}</td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c2]" value="{{ $data[$key.'_c2'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c3]" value="{{ $data[$key.'_c3'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c4]" value="{{ $data[$key.'_c4'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c5]" value="{{ $data[$key.'_c5'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c6]" value="{{ $data[$key.'_c6'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c7]" value="{{ $data[$key.'_c7'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c8]" value="{{ $data[$key.'_c8'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    </tr>
                @endforeach

                <tr class="bg-slate-50 font-semibold text-slate-700">
                    <td colspan="8" class="p-2 border border-slate-200 pl-4 italic">Biens et Mat. premières destinés aux activités de prod. et de transf.</td>
                </tr>
                @foreach(['Matières premières', 'Matières consommables', 'Pièces détachées'] as $label)
                    @php($key = 's1b_'.$loop->index)
                    <tr class="hover:bg-slate-50 border-b border-slate-200">
                        <td class="p-2 border border-slate-200 pl-8 font-medium text-slate-600">{{ $label }}</td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c2]" value="{{ $data[$key.'_c2'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c3]" value="{{ $data[$key.'_c3'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c4]" value="{{ $data[$key.'_c4'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c5]" value="{{ $data[$key.'_c5'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c6]" value="{{ $data[$key.'_c6'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c7]" value="{{ $data[$key.'_c7'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c8]" value="{{ $data[$key.'_c8'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    </tr>
                @endforeach

                <tr class="bg-slate-50 font-semibold text-slate-700">
                    <td colspan="8" class="p-2 border border-slate-200 pl-4 italic">Emballage</td>
                </tr>
                @foreach(['récupérables', 'vendus', 'perdus'] as $label)
                    @php($key = 's1c_'.$loop->index)
                    <tr class="hover:bg-slate-50 border-b border-slate-200">
                        <td class="p-2 border border-slate-200 pl-8 font-medium text-slate-600">{{ $label }}</td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c2]" value="{{ $data[$key.'_c2'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c3]" value="{{ $data[$key.'_c3'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c4]" value="{{ $data[$key.'_c4'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c5]" value="{{ $data[$key.'_c5'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c6]" value="{{ $data[$key.'_c6'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c7]" value="{{ $data[$key.'_c7'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c8]" value="{{ $data[$key.'_c8'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    </tr>
                @endforeach

                <tr class="bg-slate-100 font-bold text-slate-900 border-y border-slate-400">
                    <td class="p-2 border border-slate-300 uppercase text-right pr-3 text-blue-900">Total Stocks Approvisionnement</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                </tr>

                {{-- SECTION II --}}
                <tr class="bg-slate-200 font-bold text-slate-900 border-t border-slate-300 uppercase">
                    <td colspan="8" class="p-2 border border-slate-300 tracking-wide">II. Stocks En-cours Production de Biens et Service</td>
                </tr>
                @foreach(['Produits en cours', 'Etudes en cours', 'Travaux en cours', 'Services en cours'] as $label)
                    @php($key = 's2_'.$loop->index)
                    <tr class="hover:bg-slate-50 border-b border-slate-200">
                        <td class="p-2 border border-slate-200 pl-6 font-medium text-slate-600">{{ $label }}</td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c2]" value="{{ $data[$key.'_c2'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c3]" value="{{ $data[$key.'_c3'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c4]" value="{{ $data[$key.'_c4'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c5]" value="{{ $data[$key.'_c5'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c6]" value="{{ $data[$key.'_c6'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c7]" value="{{ $data[$key.'_c7'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c8]" value="{{ $data[$key.'_c8'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    </tr>
                @endforeach

                <tr class="bg-slate-100 font-bold text-slate-900 border-y border-slate-400">
                    <td class="p-2 border border-slate-300 uppercase text-right pr-3 text-blue-900">Total Stocks En-cours Production de Biens et Service</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                </tr>

                {{-- SECTION III --}}
                <tr class="bg-slate-200 font-bold text-slate-900 border-t border-slate-300 uppercase">
                    <td colspan="8" class="p-2 border border-slate-300 tracking-wide">III. Stocks Produits finis</td>
                </tr>
                @foreach(['Produits finis', 'Biens finis'] as $label)
                    @php($key = 's3_'.$loop->index)
                    <tr class="hover:bg-slate-50 border-b border-slate-200">
                        <td class="p-2 border border-slate-200 pl-6 font-medium text-slate-600">{{ $label }}</td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c2]" value="{{ $data[$key.'_c2'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c3]" value="{{ $data[$key.'_c3'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c4]" value="{{ $data[$key.'_c4'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c5]" value="{{ $data[$key.'_c5'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c6]" value="{{ $data[$key.'_c6'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c7]" value="{{ $data[$key.'_c7'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c8]" value="{{ $data[$key.'_c8'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    </tr>
                @endforeach

                <tr class="bg-slate-100 font-bold text-slate-900 border-y border-slate-400">
                    <td class="p-2 border border-slate-300 uppercase text-right pr-3 text-blue-900">Total Stocks Produits et Biens Finis</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                </tr>

                {{-- SECTION IV --}}
                <tr class="bg-slate-200 font-bold text-slate-900 border-t border-slate-300 uppercase">
                    <td colspan="8" class="p-2 border border-slate-300 tracking-wide">IV. Stocks Produits Résiduels</td>
                </tr>
                @foreach(['Déchets', 'Rebuts', 'Matières de récupération'] as $label)
                    @php($key = 's4_'.$loop->index)
                    <tr class="hover:bg-slate-50 border-b border-slate-200">
                        <td class="p-2 border border-slate-200 pl-6 font-medium text-slate-600">{{ $label }}</td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c2]" value="{{ $data[$key.'_c2'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c3]" value="{{ $data[$key.'_c3'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c4]" value="{{ $data[$key.'_c4'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c5]" value="{{ $data[$key.'_c5'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c6]" value="{{ $data[$key.'_c6'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c7]" value="{{ $data[$key.'_c7'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[{{ $key }}_c8]" value="{{ $data[$key.'_c8'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    </tr>
                @endforeach

                <tr class="bg-slate-100 font-bold text-slate-900 border-y border-slate-400">
                    <td class="p-2 border border-slate-300 uppercase text-right pr-3 text-blue-900">Total Stocks Produits Résiduels</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                </tr>

                {{-- TOTAL GENERAL --}}
                <tr class="bg-slate-200 font-bold text-slate-900 border-y border-slate-400 uppercase">
                    <td class="p-2 border border-slate-300 text-right pr-3 tracking-wide">TOTAL GENERAL (Ligne 10+15+18+22)</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">&nbsp;</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="mt-4 flex justify-end"><button type="submit" class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">💾 Enregistrer le tableau</button></div>
    </form>
</div>
@endsection
