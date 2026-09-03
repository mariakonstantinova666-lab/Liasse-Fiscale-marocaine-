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

    $totalMontantAnnuel = 0.0;
    $totalLoyerCharges = 0.0;

    for ($i = 0; $i < 18; $i++) {
        $totalMontantAnnuel += $parseAmount($data['r'.$i.'_c10'] ?? 0);
        $totalLoyerCharges += $parseAmount($data['r'.$i.'_c11'] ?? 0);
    }
@endphp
<div class="bg-white shadow-lg rounded-sm border border-slate-200 p-6">
    <form method="POST" action="{{ route('liasse.save', 'locations_baux') }}">
    @csrf
    <div class="liasse-page-header">
        <div class="liasse-page-heading">
            <h2 class="liasse-page-title">
                Tableau T19 — TABLEAU DES LOCATIONS ET BAUX AUTRES QUE LE CREDIT-BAIL
            </h2>
            <div class="liasse-page-meta">
                <span class="liasse-page-meta-item liasse-page-meta-exercise">Exercice : <strong>{{ $exercice ?? session('annee_exercice', 2025) }}</strong></span>
                <span class="liasse-page-meta-item liasse-page-meta-closing">Clôture : <strong>31/12/{{ $exercice ?? session('annee_exercice', 2025) }}</strong></span>
            </div>
        </div>
        <span class="liasse-page-badge">Tableau T19</span>
    </div>

    <p class="mb-2 text-right text-xs font-medium text-slate-500 dark:text-slate-400">
        Faites défiler horizontalement pour accéder aux colonnes 10 à 13 <span aria-hidden="true">→</span>
    </p>
    <div class="overflow-x-auto rounded-sm border border-slate-300 dark:border-slate-700" tabindex="0" aria-label="Tableau T19 à défilement horizontal">
        <table class="w-full text-xs text-left border-collapse border border-slate-300" style="min-width: 1700px;">
            <thead class="text-white text-center font-bold">
                <tr>
                    <th rowspan="2" class="p-2 border border-slate-300 align-middle bg-slate-800">Nature du bien loué</th>
                    <th rowspan="2" class="p-2 border border-slate-300 align-middle bg-slate-800">Lieu de situation</th>
                    <th colspan="6" class="p-2 border border-slate-300 bg-slate-800">Propriétaire</th>
                    <th rowspan="2" class="p-2 border border-slate-300 align-middle bg-slate-800">Date de conclusion de l'acte de location</th>
                    <th rowspan="2" class="p-2 border border-slate-300 align-middle bg-slate-800">Montant annuel de location</th>
                    <th rowspan="2" class="p-2 border border-slate-300 align-middle bg-slate-800">Montant du loyer compris dans les charges de l'exercice</th>
                    <th colspan="2" class="p-2 border border-slate-300 bg-slate-800">Nature du contrat (1)</th>
                </tr>
                <tr>
                    <th class="p-2 border border-slate-300 bg-slate-800 font-medium">Nom et prénoms</th>
                    <th class="p-2 border border-slate-300 bg-slate-800 font-medium">Raison sociale</th>
                    <th class="p-2 border border-slate-300 bg-slate-800 font-medium">Adresse</th>
                    <th class="p-2 border border-slate-300 bg-slate-800 font-medium">N° IF</th>
                    <th class="p-2 border border-slate-300 bg-slate-800 font-medium">N° CIN</th>
                    <th class="p-2 border border-slate-300 bg-slate-800 font-medium">N° Carte d'Étranger</th>
                    <th class="p-2 border border-slate-300 bg-slate-800 font-medium">Bail ordinaire</th>
                    <th class="p-2 border border-slate-300 bg-slate-800 font-medium">Leasing (Nème période)</th>
                </tr>
            </thead>
            <tbody>
                @for($i = 0; $i < 18; $i++)
                    <tr class="hover:bg-slate-50 border-b border-slate-200">
                        <td class="p-1 border border-slate-200"><input type="text" name="f[r{{ $i }}_c1]" value="{{ $data['r'.$i.'_c1'] ?? '' }}" class="w-full bg-transparent text-left font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[r{{ $i }}_c2]" value="{{ $data['r'.$i.'_c2'] ?? '' }}" class="w-full bg-transparent text-left font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[r{{ $i }}_c3]" value="{{ $data['r'.$i.'_c3'] ?? '' }}" class="w-full bg-transparent text-left font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[r{{ $i }}_c4]" value="{{ $data['r'.$i.'_c4'] ?? '' }}" class="w-full bg-transparent text-left font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[r{{ $i }}_c5]" value="{{ $data['r'.$i.'_c5'] ?? '' }}" class="w-full bg-transparent text-left font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[r{{ $i }}_c6]" value="{{ $data['r'.$i.'_c6'] ?? '' }}" class="w-full bg-transparent text-left font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[r{{ $i }}_c7]" value="{{ $data['r'.$i.'_c7'] ?? '' }}" class="w-full bg-transparent text-left font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[r{{ $i }}_c8]" value="{{ $data['r'.$i.'_c8'] ?? '' }}" class="w-full bg-transparent text-left font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[r{{ $i }}_c9]" value="{{ $data['r'.$i.'_c9'] ?? '' }}" class="w-full bg-transparent text-left font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[r{{ $i }}_c10]" value="{{ $data['r'.$i.'_c10'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[r{{ $i }}_c11]" value="{{ $data['r'.$i.'_c11'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[r{{ $i }}_c12]" value="{{ $data['r'.$i.'_c12'] ?? '' }}" class="w-full bg-transparent text-center font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                        <td class="p-1 border border-slate-200"><input type="text" name="f[r{{ $i }}_c13]" value="{{ $data['r'.$i.'_c13'] ?? '' }}" class="w-full bg-transparent text-center font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    </tr>
                @endfor

                <tr class="bg-slate-100 font-bold text-slate-900 border-y border-slate-400">
                    <td colspan="9" class="p-2 border border-slate-300 uppercase text-right pr-3 text-blue-900">Total</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">{{ number_format($totalMontantAnnuel, 2, ',', ' ') }}</td>
                    <td class="p-2 border border-slate-300 text-right font-mono">{{ number_format($totalLoyerCharges, 2, ',', ' ') }}</td>
                    <td class="p-2 border border-slate-300 text-center">&nbsp;</td>
                    <td class="p-2 border border-slate-300 text-center">&nbsp;</td>
                </tr>
            </tbody>
        </table>
    </div>
    <p class="text-[10px] text-slate-400 mt-2">(1) Marquer d'une croix la colonne adéquate.</p>
    <div class="mt-4 flex justify-end"><button type="submit" class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">💾 Enregistrer le tableau</button></div>
    </form>
</div>
@endsection
