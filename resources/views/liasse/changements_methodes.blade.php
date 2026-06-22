@extends('layouts.app')

@section('content')
<div class="bg-white shadow-lg rounded-sm border border-slate-200 p-6">
    <form method="POST" action="{{ route('liasse.save', 'changements_methodes') }}">
    @csrf
    <div class="flex justify-between items-center mb-4 border-b pb-2">
        <div>
            <h2 class="text-xl font-bold text-slate-800 uppercase tracking-wider">
                État des changements de méthodes
            </h2>
            <p class="text-sm text-slate-500 mt-1">Exercice : <strong>{{ $exercice ?? session('annee_exercice', 2025) }}</strong></p>
        </div>
        <span class="text-sm font-semibold bg-slate-100 px-3 py-1 rounded text-slate-600">Tableau N° 25 — Exercice {{ $exercice ?? session('annee_exercice', 2025) }}</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-xs text-left border-collapse border border-slate-300" style="min-width: 900px;">
            <thead class="text-white text-center font-bold">
                <tr>
                    <th class="p-2 border border-slate-300 bg-slate-800 text-left" style="width: 30%;">Nature des Changements</th>
                    <th class="p-2 border border-slate-300 bg-slate-800 text-left" style="width: 30%;">Justification des Changements</th>
                    <th class="p-2 border border-slate-300 bg-slate-800 text-left">Influence des Changements sur le Patrimoine, la Situation Financière et les Résultats</th>
                </tr>
            </thead>
            <tbody>
                @foreach([
                    'I. Changements affectant les méthodes d\'évaluation',
                    'II. Changements affectant les règles de présentation',
                ] as $titre)
                    @php $s = $loop->index; @endphp
                    <tr class="bg-slate-200 font-bold text-slate-900 border-t border-slate-300">
                        <td colspan="3" class="p-2 border border-slate-300 uppercase tracking-wide">{{ $titre }}</td>
                    </tr>
                    @for($i = 0; $i < 3; $i++)
                        <tr class="hover:bg-slate-50 border-b border-slate-200">
                            <td class="p-1 border border-slate-200 align-top"><textarea name="f[changement_{{ $s }}_{{ $i }}_nature]" rows="2" class="w-full bg-transparent px-1 py-1 focus:bg-yellow-50 outline-none rounded resize-y">{{ $data['changement_'.$s.'_'.$i.'_nature'] ?? '' }}</textarea></td>
                            <td class="p-1 border border-slate-200 align-top"><textarea name="f[changement_{{ $s }}_{{ $i }}_justification]" rows="2" class="w-full bg-transparent px-1 py-1 focus:bg-yellow-50 outline-none rounded resize-y">{{ $data['changement_'.$s.'_'.$i.'_justification'] ?? '' }}</textarea></td>
                            <td class="p-1 border border-slate-200 align-top"><textarea name="f[changement_{{ $s }}_{{ $i }}_influence]" rows="2" class="w-full bg-transparent px-1 py-1 focus:bg-yellow-50 outline-none rounded resize-y">{{ $data['changement_'.$s.'_'.$i.'_influence'] ?? '' }}</textarea></td>
                        </tr>
                    @endfor
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4 flex justify-end"><button type="submit" class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">💾 Enregistrer le tableau</button></div>
    </form>
</div>
@endsection
