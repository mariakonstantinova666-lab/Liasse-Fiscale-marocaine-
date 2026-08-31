@extends('layouts.app')

@section('content')
@php $fmt = fn ($v) => number_format((float) $v, 2, ',', ' '); @endphp
<div class="max-w-4xl mx-auto">
    <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900 dark:border-blue-800 dark:bg-blue-500/10 dark:text-blue-200">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="font-bold">Generation EDI XML</p>
                <p class="mt-1">Le fichier XML sera genere uniquement si aucun controle bloquant n'est detecte.</p>
            </div>
            <a href="{{ route('liasse.edi.index') }}" class="inline-flex justify-center rounded-lg bg-blue-700 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-blue-800">
                Preparer le fichier EDI (XML)
            </a>
        </div>
    </div>
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-slate-800 dark:text-slate-100">Contrôle de cohérence — Exercice {{ $exercice }}</h2>
        <span class="text-sm font-semibold bg-slate-100 px-3 py-1 rounded text-slate-600 dark:bg-slate-800 dark:text-slate-300">Modèle marocain</span>
    </div>

    {{-- Bannière de synthèse : autorise ou bloque la validation finale --}}
    @if($valide)
        <div class="mb-6 p-4 rounded-lg bg-green-50 border border-green-300 flex items-center gap-3 dark:border-green-800 dark:bg-green-500/10">
            <span class="text-2xl">✅</span>
            <div>
                <p class="font-bold text-green-800 dark:text-green-300">Liasse cohérente — validation autorisée</p>
                <p class="text-sm text-green-700 dark:text-green-400">
                    Aucune anomalie bloquante détectée.
                    @if($anomalies > 0) ({{ $anomalies }} avertissement(s) non bloquant(s)) @endif
                </p>
            </div>
        </div>
    @else
        <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-300 flex items-center gap-3 dark:border-red-800 dark:bg-red-500/10">
            <span class="text-2xl">⛔</span>
            <div>
                <p class="font-bold text-red-800 dark:text-red-300">Validation bloquée — {{ $bloquants }} anomalie(s) bloquante(s)</p>
                <p class="text-sm text-red-700 dark:text-red-400">Corrigez les contrôles en rouge ci-dessous avant de générer la liasse.</p>
            </div>
        </div>
    @endif

    <div class="space-y-3">
        @foreach($controles as $regle)
            <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 dark:bg-slate-900 {{ $regle['ok'] ? 'border-green-500' : ($regle['bloquant'] ? 'border-red-500' : 'border-amber-500') }}">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="font-bold text-slate-700 flex items-center gap-2 dark:text-slate-200">
                            {{ $regle['titre'] }}
                            <span class="text-[10px] font-semibold uppercase bg-slate-100 text-slate-500 px-2 py-0.5 rounded-full dark:bg-slate-800 dark:text-slate-400">{{ $regle['id'] ?? 'CTRL' }}</span>
                            @unless($regle['bloquant'])
                                <span class="text-[10px] font-semibold uppercase bg-slate-100 text-slate-500 px-2 py-0.5 rounded-full dark:bg-slate-800 dark:text-slate-400">non bloquant</span>
                            @endunless
                        </h3>
                        <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-400">
                            {{ $regle['severity'] ?? ($regle['bloquant'] ? 'Erreur' : 'Avertissement') }}
                            @if(!empty($regle['tableau'])) · {{ $regle['tableau'] }} @endif
                            @if(!empty($regle['rubrique'])) · {{ $regle['rubrique'] }} @endif
                        </p>
                        <p class="text-sm text-slate-500 mt-1 dark:text-slate-400">{{ $regle['message'] }}</p>
                        @if(!empty($regle['regle']))
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400"><strong>Règle :</strong> {{ $regle['regle'] }}</p>
                        @endif
                        @if(!empty($regle['suggestion']))
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400"><strong>Suggestion :</strong> {{ $regle['suggestion'] }}</p>
                        @endif
                    </div>
                    <span class="text-lg font-bold {{ $regle['ok'] ? 'text-green-600' : ($regle['bloquant'] ? 'text-red-600' : 'text-amber-600') }}">
                        {{ $regle['ok'] ? '✅' : '❌' }}
                    </span>
                </div>
                @unless($regle['ok'])
                    <p class="text-sm font-mono text-slate-600 mt-2 dark:text-slate-300">Écart : <strong>{{ $fmt($regle['ecart']) }}</strong></p>
                @endunless
            </div>
        @endforeach
    </div>
</div>
@endsection

