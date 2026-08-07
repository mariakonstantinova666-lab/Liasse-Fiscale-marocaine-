@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-6xl space-y-6">
    <div class="flex flex-col gap-4 rounded-lg border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700">Tele-declaration</p>
            <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-900">Generation du fichier EDI (XML)</h2>
            <p class="mt-2 max-w-3xl text-sm text-slate-600">
                Le fichier est genere a partir des donnees de liasse disponibles pour l'exercice {{ $exercice }}.
                Le moteur de controle est execute avant chaque generation.
            </p>
        </div>
        <span class="inline-flex w-fit items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">
            Exercice {{ $exercice }}
        </span>
    </div>

    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <p class="font-bold">{{ session('error') }}</p>
            <p class="mt-1">Corrigez les erreurs bloquantes listees ci-dessous, puis relancez la generation.</p>
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase text-slate-400">Societe</p>
            <p class="mt-2 truncate text-lg font-black text-slate-900">{{ $societe?->nom_societe ?? 'Non renseignee' }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase text-slate-400">Balance N</p>
            <p class="mt-2 text-lg font-black text-slate-900">{{ $nombreLignesBalance }} lignes</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase text-slate-400">Balance N-1</p>
            <p class="mt-2 text-lg font-black text-slate-900">{{ $nombreLignesBalancePrecedente }} lignes</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase text-slate-400">Champs liasse</p>
            <p class="mt-2 text-lg font-black text-slate-900">{{ $nombreChamps }} champs</p>
        </div>
    </div>

    <div class="rounded-lg border {{ $bloquantsAffiches->isEmpty() ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50' }} p-5">
        @if($bloquantsAffiches->isEmpty())
            <p class="font-bold text-emerald-900">Generation autorisee</p>
            <p class="mt-1 text-sm text-emerald-800">
                Aucune erreur bloquante detectee. Le fichier XML peut etre cree.
                @if($avertissements > 0)
                    {{ $avertissements }} avertissement(s) non bloquant(s) seront conserves dans le bloc de controle du XML.
                @endif
            </p>
        @else
            <p class="font-bold text-red-900">Generation bloquee : {{ $bloquantsAffiches->count() }} erreur(s) bloquante(s)</p>
            <p class="mt-1 text-sm text-red-800">
                Le fichier EDI ne doit pas etre cree tant que ces controles ou validations XML ne sont pas corriges.
            </p>
        @endif
    </div>

    @if($bloquantsAffiches->isNotEmpty())
        <div class="space-y-3">
            @foreach($bloquantsAffiches as $regle)
                <div class="rounded-lg border-l-4 border-red-500 bg-white p-4 shadow-sm">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="font-bold text-slate-800">{{ $regle['titre'] }}</h3>
                            <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-400">
                                {{ $regle['id'] ?? 'CTRL' }}
                                @if(!empty($regle['tableau'])) · {{ $regle['tableau'] }} @endif
                                @if(!empty($regle['rubrique'])) · {{ $regle['rubrique'] }} @endif
                            </p>
                            <p class="mt-2 text-sm text-slate-600">{{ $regle['message'] }}</p>
                            @if(!empty($regle['suggestion']))
                                <p class="mt-2 text-sm text-slate-500"><strong>Correction :</strong> {{ $regle['suggestion'] }}</p>
                            @endif
                        </div>
                        <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-bold text-red-700">Bloquant</span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="font-bold text-slate-900">Export XML</p>
            <p class="mt-1 text-sm text-slate-500">Le telechargement demarre automatiquement apres generation.</p>
        </div>
        <form id="edi-form" method="POST" action="{{ route('liasse.edi.generate') }}">
            @csrf
            <button
                id="edi-submit"
                type="submit"
                @disabled($bloquants->isNotEmpty())
                class="inline-flex items-center justify-center rounded-lg px-5 py-3 text-sm font-bold shadow-sm transition {{ $bloquants->isEmpty() ? 'bg-blue-700 text-white hover:bg-blue-800' : 'cursor-not-allowed bg-slate-200 text-slate-500' }}"
            >
                Generer le fichier EDI (XML)
            </button>
        </form>
    </div>
</div>

<script>
    (() => {
        const form = document.getElementById('edi-form');
        const button = document.getElementById('edi-submit');
        form?.addEventListener('submit', () => {
            if (!button) return;
            button.disabled = true;
            button.textContent = 'Generation en cours...';
            button.classList.add('opacity-75');
        });
    })();
</script>
@endsection
