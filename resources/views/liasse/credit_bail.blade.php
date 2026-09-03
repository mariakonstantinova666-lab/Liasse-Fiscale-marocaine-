@extends('layouts.app')

@section('content')
@php
    $parseAmount = static function ($value): float {
        $normalized = str_replace(["\xC2\xA0", ' '], '', trim((string) $value));

        if (str_contains($normalized, ',')) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        }

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    };

    $visualTotals = array_fill_keys([4, 6, 7, 8, 9, 10], 0.0);
    for ($i = 0; $i < 18; $i++) {
        foreach (array_keys($visualTotals) as $column) {
            $visualTotals[$column] += $parseAmount($data['r'.$i.'_c'.$column] ?? 0);
        }
    }
@endphp
<div class="bg-white shadow-lg rounded-sm border border-slate-200 p-6">
    <form method="POST" action="{{ route('liasse.save', 'credit_bail') }}">
    @csrf
    <div class="liasse-page-header">
        <div class="liasse-page-heading">
            <h2 class="liasse-page-title">
                Tableau T07 — TABLEAU DES BIENS EN CREDIT BAIL
            </h2>
            <div class="liasse-page-meta">
                <span class="liasse-page-meta-item liasse-page-meta-exercise">Exercice : <strong>{{ $exercice }}</strong></span>
                <span class="liasse-page-meta-item liasse-page-meta-closing">Clôture : <strong>31/12/{{ $exercice }}</strong></span>
            </div>
        </div>
        <span class="liasse-page-badge">Tableau T07</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-xs text-left border-collapse border border-slate-300" style="min-width: 1400px;">
            <thead class="text-white text-center font-bold">
                <tr>
                    <th rowspan="2" class="p-2 border border-slate-300 align-middle bg-slate-800 text-left">Rubriques</th>
                    <th rowspan="2" class="p-2 border border-slate-300 align-middle bg-slate-800">Date de la 1ère échéance</th>
                    <th rowspan="2" class="p-2 border border-slate-300 align-middle bg-slate-800">Durée du contrat en mois</th>
                    <th rowspan="2" class="p-2 border border-slate-300 align-middle bg-slate-800">Valeur estimée du bien à la date du contrat</th>
                    <th rowspan="2" class="p-2 border border-slate-300 align-middle bg-slate-800">Durée théorique d'amortis. du bien</th>
                    <th rowspan="2" class="p-2 border border-slate-300 align-middle bg-slate-800">Cumul des exercices précédents des redevances</th>
                    <th rowspan="2" class="p-2 border border-slate-300 align-middle bg-slate-800">Montant de l'exercice des redevances</th>
                    <th colspan="2" class="p-2 border border-slate-300 bg-slate-800">Redevances restant à payer</th>
                    <th rowspan="2" class="p-2 border border-slate-300 align-middle bg-slate-800">Prix d'achat résiduel en fin de contrat</th>
                    <th rowspan="2" class="p-2 border border-slate-300 align-middle bg-slate-800">Observations</th>
                </tr>
                <tr>
                    <th class="p-2 border border-slate-300 bg-slate-800 font-medium">à moins d'un an</th>
                    <th class="p-2 border border-slate-300 bg-slate-800 font-medium">à plus d'un an</th>
                </tr>
            </thead>
            <tbody>
                @for($i = 0; $i < 18; $i++)
                    <tr class="hover:bg-slate-50 border-b border-slate-200">
                        <td class="p-1 border border-slate-200"><input type="text" name="f[r{{ $i }}_c1]" value="{{ $data['r'.$i.'_c1'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[r{{ $i }}_c2]" value="{{ $data['r'.$i.'_c2'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[r{{ $i }}_c3]" value="{{ $data['r'.$i.'_c3'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[r{{ $i }}_c4]" value="{{ $data['r'.$i.'_c4'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[r{{ $i }}_c5]" value="{{ $data['r'.$i.'_c5'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[r{{ $i }}_c6]" value="{{ $data['r'.$i.'_c6'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[r{{ $i }}_c7]" value="{{ $data['r'.$i.'_c7'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[r{{ $i }}_c8]" value="{{ $data['r'.$i.'_c8'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[r{{ $i }}_c9]" value="{{ $data['r'.$i.'_c9'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[r{{ $i }}_c10]" value="{{ $data['r'.$i.'_c10'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[r{{ $i }}_c11]" value="{{ $data['r'.$i.'_c11'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    </tr>
                @endfor
                <tr class="bg-slate-100 font-bold text-slate-900 border-y border-slate-400">
                    <td class="p-2 border border-slate-300 uppercase text-left pr-3">Total</td>
                    <td class="p-2 border border-slate-300">&nbsp;</td>
                    <td class="p-2 border border-slate-300">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">{{ number_format($visualTotals[4], 2, ',', ' ') }}</td>
                    <td class="p-2 border border-slate-300 text-center">-</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">{{ number_format($visualTotals[6], 2, ',', ' ') }}</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">{{ number_format($visualTotals[7], 2, ',', ' ') }}</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">{{ number_format($visualTotals[8], 2, ',', ' ') }}</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">{{ number_format($visualTotals[9], 2, ',', ' ') }}</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">{{ number_format($visualTotals[10], 2, ',', ' ') }}</td>
                    <td class="p-2 border border-slate-300 text-center">-</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="mt-4 flex justify-end"><button type="submit" class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">💾 Enregistrer le tableau</button></div>
    </form>
</div>
@endsection
