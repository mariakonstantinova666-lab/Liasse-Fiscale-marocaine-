@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700 dark:text-blue-300">Nouvel import</p>
        <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-slate-100">Importer un dossier fiscal source</h1>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Importez le classeur unique qui regroupe les documents sources. L'application detecte automatiquement les tableaux fiscaux a alimenter.</p>
    </div>

    @if($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 dark:border-rose-800 dark:bg-rose-500/10 dark:text-rose-200">
            <p class="font-bold">Import impossible</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('source-documents.store') }}" enctype="multipart/form-data" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
        @csrf
        <input type="hidden" name="document_type" value="dossier_fiscal_complet">
        <input type="hidden" name="tableau_code" value="multi_tableaux">

        <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(320px,420px)]">
            <div class="space-y-5">
                <div>
                    <p class="mb-1.5 text-sm font-semibold text-slate-700 dark:text-slate-300">Exercice fiscal</p>
                    <div class="rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm font-bold text-slate-900 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                        Exercice : {{ $exercice }}
                    </div>
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Pour changer d'exercice, utilisez le sélecteur global.</p>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-300">Fichier source</label>
                    <input type="file" name="document" required accept=".xlsx,.xls,.csv,.txt,.pdf" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-blue-800 focus:border-blue-600 focus:ring-blue-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:file:bg-slate-700 dark:file:text-slate-100 dark:focus:border-blue-500 dark:focus:ring-blue-500">
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Formats acceptes : Excel, CSV, TXT ou PDF. Les fichiers Excel sont analyses automatiquement.</p>
                </div>
            </div>

            <div class="rounded-lg border border-blue-100 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-500/10">
                <p class="text-sm font-bold text-blue-950 dark:text-blue-200">Tableaux pouvant être alimentés par des documents sources</p>
                <p class="mt-1 text-sm text-blue-800 dark:text-blue-300">Selon le tableau, les données peuvent être extraites automatiquement, vérifiées ou complétées manuellement.</p>
                <div class="mt-4 grid grid-cols-2 gap-2 text-xs font-semibold text-blue-900 dark:text-blue-200">
                    @foreach(['T03', 'T07', 'T10', 'T11', 'T13', 'T14', 'T15', 'T16', 'T17', 'T18', 'T19', 'T21', 'T23', 'T24', 'T25', 'T26'] as $code)
                        <span class="rounded-md border border-blue-200 bg-white px-2.5 py-2 text-center dark:border-blue-700 dark:bg-slate-800">{{ $code }}</span>
                    @endforeach
                </div>
                <p class="mt-3 text-xs text-blue-700 dark:text-blue-300">Les champs specifiques restent tracés et peuvent etre verifies apres l'analyse.</p>
            </div>
        </div>

        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 dark:border-slate-700 sm:flex-row sm:justify-end">
            <a href="{{ route('source-documents.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 dark:focus:ring-blue-500 dark:focus:ring-offset-slate-900">Annuler</a>
            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 dark:focus:ring-blue-500 dark:focus:ring-offset-slate-900">Importer et analyser</button>
        </div>
    </form>
</div>
@endsection
