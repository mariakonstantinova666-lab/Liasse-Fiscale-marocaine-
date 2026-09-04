@extends('layouts.app')

@section('content')
@php
    $fmt = fn ($v) => number_format((float) $v, 2, ',', ' ');
    $controleCollection = collect($controles);
    $conformesCount = $controleCollection->filter(fn ($regle) => $regle['ok'] === true)->count();
    $avertissementsCount = $controleCollection->filter(fn ($regle) => $regle['ok'] === false && $regle['bloquant'] === false)->count();
    $bloquantsCount = $controleCollection->filter(fn ($regle) => $regle['ok'] === false && $regle['bloquant'] === true)->count();
@endphp

<div class="control-page">
    <header class="control-page-header">
        <div>
            <p class="control-page-eyebrow">Qualité du dossier fiscal</p>
            <h1 class="control-page-title">Contrôle de cohérence</h1>
            <p class="control-page-description">Synthèse des vérifications comptables, fiscales et déclaratives de la liasse.</p>
        </div>
        <div class="control-page-header-meta">
            <span class="control-page-meta-badge">Exercice : <strong>{{ $exercice }}</strong></span>
            <span class="control-page-model-badge">Modèle marocain</span>
        </div>
    </header>

    <section class="control-summary" aria-label="Résumé des contrôles">
        <article class="control-summary-card control-summary-card-success">
            <div class="control-summary-icon" aria-hidden="true">✓</div>
            <div>
                <p class="control-summary-label">Conformes</p>
                <p class="control-summary-value">{{ $conformesCount }}</p>
            </div>
        </article>
        <article class="control-summary-card control-summary-card-warning">
            <div class="control-summary-icon" aria-hidden="true">!</div>
            <div>
                <p class="control-summary-label">Avertissements</p>
                <p class="control-summary-value">{{ $avertissementsCount }}</p>
            </div>
        </article>
        <article class="control-summary-card control-summary-card-danger">
            <div class="control-summary-icon" aria-hidden="true">×</div>
            <div>
                <p class="control-summary-label">Bloquants</p>
                <p class="control-summary-value">{{ $bloquantsCount }}</p>
            </div>
        </article>
    </section>

    {{-- Bannière de synthèse : autorise ou bloque la validation finale --}}
    @if($valide)
        <section class="control-global-banner control-global-banner-success">
            <span class="control-global-icon" aria-hidden="true">✓</span>
            <div>
                <p class="control-global-title">Liasse cohérente — validation autorisée</p>
                <p class="control-global-text">
                    Aucune anomalie bloquante détectée.
                    @if($anomalies > 0) ({{ $anomalies }} avertissement(s) non bloquant(s)) @endif
                </p>
            </div>
        </section>
    @else
        <section class="control-global-banner control-global-banner-danger">
            <span class="control-global-icon" aria-hidden="true">!</span>
            <div>
                <p class="control-global-title">Validation bloquée — {{ $bloquants }} anomalie(s) bloquante(s)</p>
                <p class="control-global-text">Corrigez les contrôles en rouge ci-dessous avant de générer la liasse.</p>
            </div>
        </section>
    @endif

    <section class="control-edi-card">
        <div class="control-edi-copy">
            <span class="control-edi-icon" aria-hidden="true">XML</span>
            <div>
                <p class="control-edi-kicker">Étape suivante</p>
                <h2 class="control-edi-title">Génération EDI XML</h2>
                <p class="control-edi-text">Le fichier XML sera généré uniquement si aucun contrôle bloquant n'est détecté.</p>
            </div>
        </div>
        <a href="{{ route('liasse.edi.index') }}" class="control-edi-action">
            Préparer le fichier EDI (XML)
        </a>
    </section>

    <section class="control-results">
        <div class="control-results-heading">
            <div>
                <p class="control-page-eyebrow">Détail des vérifications</p>
                <h2 class="control-results-title">Résultats des contrôles</h2>
            </div>
            <span class="control-results-count">{{ $controleCollection->count() }} contrôle(s)</span>
        </div>

        <div class="control-results-list">
            @foreach($controles as $regle)
                <article class="control-result-card {{ $regle['ok'] ? 'control-result-card-success' : ($regle['bloquant'] ? 'control-result-card-danger' : 'control-result-card-warning') }}">
                    <div class="control-result-topline">
                        <div class="control-result-heading">
                            <span class="control-status-badge {{ $regle['ok'] ? 'control-status-badge-success' : ($regle['bloquant'] ? 'control-status-badge-danger' : 'control-status-badge-warning') }}">
                                {{ $regle['ok'] ? 'Conforme' : ($regle['bloquant'] ? 'Bloquant' : 'Avertissement') }}
                            </span>
                            <span class="control-id-badge">{{ $regle['id'] ?? 'CTRL' }}</span>
                            @if(!empty($regle['tableau']))
                                <span class="control-table-badge">{{ $regle['tableau'] }}</span>
                            @endif
                            @unless($regle['bloquant'])
                                <span class="control-secondary-badge">non bloquant</span>
                            @endunless
                        </div>
                        <span class="control-result-symbol" aria-hidden="true">{{ $regle['ok'] ? '✓' : ($regle['bloquant'] ? '×' : '!') }}</span>
                    </div>

                    <h3 class="control-result-title">{{ $regle['titre'] }}</h3>

                    <div class="control-result-context">
                        <span>{{ $regle['severity'] ?? ($regle['bloquant'] ? 'Erreur' : 'Avertissement') }}</span>
                        @if(!empty($regle['rubrique']))
                            <span>{{ $regle['rubrique'] }}</span>
                        @endif
                    </div>

                    <p class="control-result-message">{{ $regle['message'] }}</p>

                    @if(!empty($regle['regle']))
                        <div class="control-detail-block">
                            <p class="control-detail-label">Règle de contrôle</p>
                            <p class="control-detail-text">{{ $regle['regle'] }}</p>
                        </div>
                    @endif

                    @if(!empty($regle['suggestion']))
                        <div class="control-help-block">
                            <p class="control-detail-label">Suggestion</p>
                            <p class="control-detail-text">{{ $regle['suggestion'] }}</p>
                        </div>
                    @endif

                    @unless($regle['ok'])
                        <div class="control-gap-row">
                            <span>Écart constaté</span>
                            <strong>{{ $fmt($regle['ecart']) }}</strong>
                        </div>
                    @endunless
                </article>
            @endforeach
        </div>
    </section>
</div>
@endsection
