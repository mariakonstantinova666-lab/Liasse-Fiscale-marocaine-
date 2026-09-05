@extends('layouts.app')

@section('content')
<div class="edi-page">
    <header class="edi-header">
        <div class="edi-header-copy">
            <p class="edi-eyebrow">Télé-déclaration fiscale</p>
            <h1 class="edi-title">Préparation EDI / XML</h1>
            <p class="edi-description">
                Préparez le fichier XML à partir des données de liasse disponibles. Le moteur de contrôle est exécuté avant chaque génération.
            </p>
        </div>
        <div class="edi-header-meta">
            <span class="edi-meta-badge">Exercice : <strong>{{ $exercice }}</strong></span>
            <span class="edi-company-badge">{{ $societe?->nom_societe ?? 'Société non renseignée' }}</span>
            <span class="edi-status-badge {{ $bloquants->isEmpty() ? 'edi-status-badge-ready' : 'edi-status-badge-blocked' }}">
                {{ $bloquants->isEmpty() ? 'Génération autorisée' : 'Génération bloquée' }}
            </span>
        </div>
    </header>

    <nav class="edi-progress" aria-label="Parcours de préparation EDI">
        <div class="edi-progress-step">
            <span class="edi-progress-number">1</span>
            <span>Liasse</span>
        </div>
        <span class="edi-progress-arrow" aria-hidden="true">→</span>
        <div class="edi-progress-step">
            <span class="edi-progress-number">2</span>
            <span>Contrôles</span>
        </div>
        <span class="edi-progress-arrow" aria-hidden="true">→</span>
        <div class="edi-progress-step edi-progress-step-current">
            <span class="edi-progress-number">3</span>
            <span>Préparation EDI</span>
        </div>
        <span class="edi-progress-arrow" aria-hidden="true">→</span>
        <div class="edi-progress-step">
            <span class="edi-progress-number">4</span>
            <span>Génération XML</span>
        </div>
    </nav>

    @if(session('error'))
        <section class="edi-alert edi-alert-danger" role="alert">
            <span class="edi-alert-icon" aria-hidden="true">!</span>
            <div>
                <p class="edi-alert-title">{{ session('error') }}</p>
                <p class="edi-alert-text">Corrigez les erreurs bloquantes listées ci-dessous, puis relancez la génération.</p>
            </div>
        </section>
    @endif

    <section class="edi-section">
        <div class="edi-section-heading">
            <div>
                <p class="edi-eyebrow">Informations disponibles</p>
                <h2 class="edi-section-title">Prérequis du dossier</h2>
            </div>
            <p class="edi-section-note">Ces volumes sont descriptifs et ne constituent pas une preuve de conformité.</p>
        </div>

        <div class="edi-info-grid">
            <article class="edi-info-card">
                <span class="edi-info-code">N</span>
                <div>
                    <p class="edi-info-label">Balance N</p>
                    <p class="edi-info-value">{{ $nombreLignesBalance }} lignes</p>
                </div>
            </article>
            <article class="edi-info-card">
                <span class="edi-info-code">N-1</span>
                <div>
                    <p class="edi-info-label">Balance N-1</p>
                    <p class="edi-info-value">{{ $nombreLignesBalancePrecedente }} lignes</p>
                </div>
            </article>
            <article class="edi-info-card">
                <span class="edi-info-code">LF</span>
                <div>
                    <p class="edi-info-label">Champs liasse</p>
                    <p class="edi-info-value">{{ $nombreChamps }} champs</p>
                </div>
            </article>
            <article class="edi-info-card">
                <span class="edi-info-code">!</span>
                <div>
                    <p class="edi-info-label">Avertissements</p>
                    <p class="edi-info-value">{{ $avertissements }}</p>
                </div>
            </article>
        </div>
    </section>

    <section class="edi-decision {{ $bloquants->isEmpty() ? 'edi-decision-ready' : 'edi-decision-blocked' }}">
        <span class="edi-decision-icon" aria-hidden="true">{{ $bloquants->isEmpty() ? '✓' : '!' }}</span>
        <div>
            @if($bloquants->isEmpty())
                <h2 class="edi-decision-title">Génération autorisée</h2>
                <p class="edi-decision-text">
                    Aucune erreur bloquante détectée. Le fichier XML peut être créé.
                    @if($avertissements > 0)
                        {{ $avertissements }} avertissement(s) non bloquant(s) seront conservés dans le bloc de contrôle du XML.
                    @endif
                </p>
            @else
                <h2 class="edi-decision-title">Génération bloquée : {{ $bloquants->count() }} erreur(s) bloquante(s)</h2>
                <p class="edi-decision-text">
                    Le fichier EDI ne doit pas être créé tant que ces contrôles ou validations XML ne sont pas corrigés.
                </p>
            @endif
        </div>
    </section>

    @if($bloquants->isNotEmpty())
        <section class="edi-section">
            <div class="edi-section-heading">
                <div>
                    <p class="edi-eyebrow edi-eyebrow-danger">Corrections requises</p>
                    <h2 class="edi-section-title">Erreurs bloquantes</h2>
                </div>
                <span class="edi-blocking-count">{{ $bloquants->count() }} blocage(s)</span>
            </div>

            <div class="edi-error-list">
                @foreach($bloquants as $regle)
                    <article class="edi-error-card">
                        <div class="edi-error-topline">
                            <div class="edi-error-badges">
                                <span class="edi-error-status">Bloquant</span>
                                <span class="edi-error-id">{{ $regle['id'] ?? 'CTRL' }}</span>
                                @if(!empty($regle['tableau']))
                                    <span class="edi-error-table">{{ $regle['tableau'] }}</span>
                                @endif
                            </div>
                            @if(!empty($regle['rubrique']))
                                <span class="edi-error-rubrique">{{ $regle['rubrique'] }}</span>
                            @endif
                        </div>
                        <h3 class="edi-error-title">{{ $regle['titre'] }}</h3>
                        <p class="edi-error-message">{{ $regle['message'] }}</p>
                        @if(!empty($regle['suggestion']))
                            <div class="edi-error-help">
                                <p class="edi-error-help-label">Correction suggérée</p>
                                <p>{{ $regle['suggestion'] }}</p>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @if($erreursGeneration->isNotEmpty())
        <section class="edi-section">
            <div class="edi-section-heading">
                <div>
                    <p class="edi-eyebrow edi-eyebrow-danger">Dernière génération</p>
                    <h2 class="edi-section-title">Erreurs de la dernière tentative</h2>
                </div>
                <span class="edi-blocking-count">{{ $erreursGeneration->count() }} erreur(s)</span>
            </div>

            <p class="edi-section-note">
                Ces erreurs correspondent à la dernière tentative. L’autorisation actuelle est déterminée séparément par les contrôles recalculés ci-dessus.
            </p>

            <div class="edi-error-list">
                @foreach($erreursGeneration as $regle)
                    <article class="edi-error-card">
                        <div class="edi-error-topline">
                            <div class="edi-error-badges">
                                <span class="edi-error-status">Dernière tentative</span>
                                <span class="edi-error-id">{{ $regle['id'] ?? 'EDI' }}</span>
                                @if(!empty($regle['tableau']))
                                    <span class="edi-error-table">{{ $regle['tableau'] }}</span>
                                @endif
                            </div>
                            @if(!empty($regle['rubrique']))
                                <span class="edi-error-rubrique">{{ $regle['rubrique'] }}</span>
                            @endif
                        </div>
                        <h3 class="edi-error-title">{{ $regle['titre'] }}</h3>
                        <p class="edi-error-message">{{ $regle['message'] }}</p>
                        @if(!empty($regle['suggestion']))
                            <div class="edi-error-help">
                                <p class="edi-error-help-label">Correction suggérée</p>
                                <p>{{ $regle['suggestion'] }}</p>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <section class="edi-generation-card">
        <div class="edi-generation-copy">
            <span class="edi-generation-icon" aria-hidden="true">XML</span>
            <div>
                <p class="edi-eyebrow">Génération du fichier</p>
                <h2 class="edi-generation-title">Exporter la liasse au format XML</h2>
                <p class="edi-generation-text">Après génération, le téléchargement du fichier XML démarre automatiquement.</p>
            </div>
        </div>

        <form id="edi-form" method="POST" action="{{ route('liasse.edi.generate') }}">
            @csrf
            <button
                id="edi-submit"
                type="submit"
                @disabled($bloquants->isNotEmpty())
                class="edi-generate-button {{ $bloquants->isEmpty() ? 'edi-generate-button-enabled' : 'edi-generate-button-disabled' }}"
            >
                Générer le fichier EDI (XML)
            </button>
        </form>
    </section>
</div>

<script>
    (() => {
        const form = document.getElementById('edi-form');
        const button = document.getElementById('edi-submit');
        form?.addEventListener('submit', () => {
            if (!button) return;
            button.disabled = true;
            button.textContent = 'Génération en cours…';
            button.classList.add('opacity-75');
        });
    })();
</script>
@endsection
