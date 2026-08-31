@extends('layouts.app')

@section('content')
@php $fmt = fn ($v) => $v === null ? '&nbsp;' : number_format((float) $v, 2, ',', ' '); @endphp
<div class="bg-white shadow-lg rounded-sm border border-slate-200 p-6">
    <div class="flex justify-between items-center mb-4 border-b pb-2">
        <div>
            <h2 class="text-xl font-bold text-slate-800 uppercase tracking-wider">
                Tableau T06 — DETAIL DES POSTES DU C.P.C.
            </h2>
            <p class="text-sm text-slate-500 mt-1">Exercice : <strong>{{ $exercice }}</strong></p>
        </div>
        <span class="text-sm font-semibold bg-slate-100 px-3 py-1 rounded text-slate-600">Tableau T06 — Exercice {{ $exercice }}</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-xs text-left border-collapse border border-slate-300" style="min-width: 900px;">
            <thead class="text-white text-center font-bold">
                <tr>
                    <th class="p-2 border border-slate-300 align-middle bg-slate-800 text-left" style="width: 60%;">Poste</th>
                    <th class="p-2 border border-slate-300 align-middle bg-slate-800">Exercice</th>
                    <th class="p-2 border border-slate-300 align-middle bg-slate-800">Exercice Précédent</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    @if(isset($row['section']))
                        <tr class="liasse-section bg-slate-300 font-black text-slate-900 border-t border-slate-400 dark:bg-slate-800 dark:text-slate-100 dark:border-slate-600">
                            <td colspan="3" class="p-2 border border-slate-300 uppercase tracking-wide">{{ $row['section'] }}</td>
                        </tr>
                    @elseif(isset($row['poste']))
                        <tr class="liasse-subsection bg-slate-200 font-bold text-slate-900 border-t border-slate-300 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700">
                            <td colspan="3" class="p-2 border border-slate-300">{{ $row['poste'] }}</td>
                        </tr>
                    @elseif(!empty($row['total']))
                        <tr class="liasse-total-strong bg-slate-100 font-bold text-slate-900 border-y border-slate-400 dark:bg-slate-700 dark:text-white dark:border-slate-500">
                            <td class="p-2 border border-slate-300 pl-4">{{ $row['l'] }}</td>
                            <td class="p-2 border border-slate-300 text-right font-mono">{!! $fmt($row['n']) !!}</td>
                            <td class="p-2 border border-slate-300 text-right font-mono">{!! $fmt($row['p']) !!}</td>
                        </tr>
                    @else
                        <tr class="hover:bg-slate-50 border-b border-slate-200">
                            <td class="p-2 border border-slate-200 pl-6 font-medium text-slate-600">{{ $row['l'] }}</td>
                            <td class="p-2 border border-slate-200 text-right font-mono">{!! $fmt($row['n']) !!}</td>
                            <td class="p-2 border border-slate-200 text-right font-mono">{!! $fmt($row['p']) !!}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
