@extends('layouts.app')

@section('content')
@php $fmt = fn ($v) => number_format((float) $v, 2, ',', ' '); @endphp
<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-slate-800">Contrôle de cohérence — Exercice {{ $exercice }}</h2>
        <span class="text-sm font-semibold bg-slate-100 px-3 py-1 rounded text-slate-600">Modèle marocain</span>
    </div>

    {{-- Bannière de synthèse : autorise ou bloque la validation finale --}}
    @if($valide)
        <div class="mb-6 p-4 rounded-lg bg-green-50 border border-green-300 flex items-center gap-3">
            <span class="text-2xl">✅</span>
            <div>
                <p class="font-bold text-green-800">Liasse cohérente — validation autorisée</p>
                <p class="text-sm text-green-700">
                    Aucune anomalie bloquante détectée.
                    @if($anomalies > 0) ({{ $anomalies }} avertissement(s) non bloquant(s)) @endif
                </p>
            </div>
        </div>
    @else
        <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-300 flex items-center gap-3">
            <span class="text-2xl">⛔</span>
            <div>
                <p class="font-bold text-red-800">Validation bloquée — {{ $bloquants }} anomalie(s) bloquante(s)</p>
                <p class="text-sm text-red-700">Corrigez les contrôles en rouge ci-dessous avant de générer la liasse.</p>
            </div>
        </div>
    @endif

    <div class="space-y-3">
        @foreach($controles as $regle)
            <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 {{ $regle['ok'] ? 'border-green-500' : ($regle['bloquant'] ? 'border-red-500' : 'border-amber-500') }}">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="font-bold text-slate-700 flex items-center gap-2">
                            {{ $regle['titre'] }}
                            @unless($regle['bloquant'])
                                <span class="text-[10px] font-semibold uppercase bg-slate-100 text-slate-500 px-2 py-0.5 rounded-full">non bloquant</span>
                            @endunless
                        </h3>
                        <p class="text-sm text-slate-500 mt-1">{{ $regle['message'] }}</p>
                    </div>
                    <span class="text-lg font-bold {{ $regle['ok'] ? 'text-green-600' : ($regle['bloquant'] ? 'text-red-600' : 'text-amber-600') }}">
                        {{ $regle['ok'] ? '✅' : '❌' }}
                    </span>
                </div>
                @unless($regle['ok'])
                    <p class="text-sm font-mono text-slate-600 mt-2">Écart : <strong>{{ $fmt($regle['ecart']) }}</strong></p>
                @endunless
            </div>
        @endforeach
    </div>
</div>
@endsection
