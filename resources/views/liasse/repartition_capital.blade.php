@extends('layouts.app')

@section('content')
<div class="bg-white shadow-lg rounded-sm border border-slate-200 p-6">
    <form method="POST" action="{{ route('liasse.save', 'repartition_capital') }}">
    @csrf
    <div class="flex justify-between items-center mb-4 border-b pb-2">
        <div>
            <h2 class="text-xl font-bold text-slate-800 uppercase tracking-wider">
                ÉTAT DE RÉPARTITION DU CAPITAL SOCIAL
            </h2>
            <p class="text-sm text-slate-500 mt-1">Exercice : <strong>{{ $exercice }}</strong></p>
        </div>
        <span class="text-sm font-semibold bg-slate-100 px-3 py-1 rounded text-slate-600">Tableau N° 13 — Exercice {{ $exercice }}</span>
    </div>

    <p class="text-sm text-slate-600 mb-2">Montant du capital : <input type="text" name="f[montant_capital]" value="{{ $data['montant_capital'] ?? '' }}" class="bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded border border-slate-200"></p>

    <div class="overflow-x-auto">
        <table class="w-full text-xs text-left border-collapse border border-slate-300" style="min-width: 1500px;">
            <thead class="text-white text-center font-bold">
                <tr>
                    <th rowspan="2" class="p-2 border border-slate-300 align-middle bg-slate-800 text-left">Nom, prénom des principaux associés</th>
                    <th rowspan="2" class="p-2 border border-slate-300 align-middle bg-slate-800">Raison sociale des principaux associés</th>
                    <th rowspan="2" class="p-2 border border-slate-300 align-middle bg-slate-800">N° IF</th>
                    <th rowspan="2" class="p-2 border border-slate-300 align-middle bg-slate-800">N° CIN</th>
                    <th rowspan="2" class="p-2 border border-slate-300 align-middle bg-slate-800">N° Carte d'Étranger</th>
                    <th rowspan="2" class="p-2 border border-slate-300 align-middle bg-slate-800">Adresse</th>
                    <th colspan="2" class="p-2 border border-slate-300 bg-slate-800">Nombre de titres</th>
                    <th rowspan="2" class="p-2 border border-slate-300 align-middle bg-slate-800">Valeur nominale de chaque action ou part sociale</th>
                    <th colspan="3" class="p-2 border border-slate-300 bg-slate-800">Montant du capital</th>
                </tr>
                <tr>
                    <th class="p-2 border border-slate-300 bg-slate-800 font-medium">Exercice précédent</th>
                    <th class="p-2 border border-slate-300 bg-slate-800 font-medium">Exercice actuel</th>
                    <th class="p-2 border border-slate-300 bg-slate-800 font-medium">Souscrit</th>
                    <th class="p-2 border border-slate-300 bg-slate-800 font-medium">Appelé</th>
                    <th class="p-2 border border-slate-300 bg-slate-800 font-medium">Libéré</th>
                </tr>
            </thead>
            <tbody>
                @for($i = 0; $i < 8; $i++)
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
                        <td class="p-1 border border-slate-200"><input type="text" name="f[r{{ $i }}_c12]" value="{{ $data['r'.$i.'_c12'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    </tr>
                @endfor
                <tr class="bg-slate-100 font-bold text-slate-900 border-y border-slate-400">
                    <td class="p-2 border border-slate-300 uppercase text-left pr-3">Total</td>
                    <td class="p-1 border border-slate-300"><input type="text" name="f[total_c2]" value="{{ $data['total_c2'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-300"><input type="text" name="f[total_c3]" value="{{ $data['total_c3'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-300"><input type="text" name="f[total_c4]" value="{{ $data['total_c4'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-300"><input type="text" name="f[total_c5]" value="{{ $data['total_c5'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-300"><input type="text" name="f[total_c6]" value="{{ $data['total_c6'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-300"><input type="text" name="f[total_c7]" value="{{ $data['total_c7'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-300"><input type="text" name="f[total_c8]" value="{{ $data['total_c8'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-300"><input type="text" name="f[total_c9]" value="{{ $data['total_c9'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-300"><input type="text" name="f[total_c10]" value="{{ $data['total_c10'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-300"><input type="text" name="f[total_c11]" value="{{ $data['total_c11'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                    <td class="p-1 border border-slate-300"><input type="text" name="f[total_c12]" value="{{ $data['total_c12'] ?? '' }}" class="w-full bg-transparent text-right font-mono px-1 py-1 focus:bg-yellow-50 outline-none rounded"></td>
                </tr>
            </tbody>
        </table>
    </div>

    <p class="text-[10px] text-slate-400 mt-2">(1) Quand le nombre des associés est inférieur ou égal à 10, l'entreprise doit déclarer tous les participants au capital. Dans les autres cas, ne mentionner que les 10 principaux associés par ordre d'importance décroissante.</p>

    <div class="mt-4 flex justify-end"><button type="submit" class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">💾 Enregistrer le tableau</button></div>
    </form>
</div>
@endsection
