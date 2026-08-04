@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700">Nouvel import</p>
        <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-950">Importer un dossier fiscal source</h1>
        <p class="mt-2 text-sm text-slate-600">Importez le classeur unique qui regroupe les documents sources. L'application detecte automatiquement les tableaux fiscaux a alimenter.</p>
    </div>

    @if($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
            <p class="font-bold">Import impossible</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('source-documents.store') }}" enctype="multipart/form-data" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        @csrf
        <input type="hidden" name="document_type" value="dossier_fiscal_complet">
        <input type="hidden" name="tableau_code" value="multi_tableaux">

        <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(320px,420px)]">
            <div class="space-y-5">
                <label class="mb-1.5 block text-sm font-semibold text-slate-700">Exercice fiscal</label>
                <input type="number" name="exercice" value="{{ old('exercice', session('annee_exercice', 2026)) }}" required class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600">

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Fichier source</label>
                    <input type="file" name="document" required accept=".xlsx,.xls,.csv,.txt,.pdf" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-blue-800 focus:border-blue-600 focus:ring-blue-600">
                    <p class="mt-2 text-xs text-slate-500">Formats acceptes : Excel, CSV, TXT ou PDF. Les fichiers Excel sont analyses automatiquement.</p>
                </div>
            </div>

            <div class="rounded-lg border border-blue-100 bg-blue-50 p-4">
                <p class="text-sm font-bold text-blue-950">Detection automatique</p>
                <p class="mt-1 text-sm text-blue-800">Un seul import peut alimenter plusieurs tableaux de la liasse.</p>
                <div class="mt-4 grid grid-cols-2 gap-2 text-xs font-semibold text-blue-900">
                    @foreach(['T03', 'T13', 'T14', 'T16', 'T19', 'T23', 'T24', 'T25'] as $code)
                        <span class="rounded-md border border-blue-200 bg-white px-2.5 py-2 text-center">{{ $code }}</span>
                    @endforeach
                </div>
                <p class="mt-3 text-xs text-blue-700">Les champs specifiques restent tracés et peuvent etre verifies apres l'analyse.</p>
            </div>
        </div>

        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
            <a href="{{ route('source-documents.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Annuler</a>
            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800">Importer et analyser</button>
        </div>
    </form>
</div>
@endsection
