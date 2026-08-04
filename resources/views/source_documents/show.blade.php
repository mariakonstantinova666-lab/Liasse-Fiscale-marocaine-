@extends('layouts.app')

@section('content')
@php
    $statusClass = match ($document->status) {
        'validated' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'needs_validation' => 'border-amber-200 bg-amber-50 text-amber-700',
        'error' => 'border-rose-200 bg-rose-50 text-rose-700',
        'analyzing' => 'border-blue-200 bg-blue-50 text-blue-700',
        default => 'border-slate-200 bg-slate-50 text-slate-700',
    };
    $mappedData = $document->extraction?->mapped_data ?? [];
    $rawData = $document->extraction?->raw_data ?? [];
    $errors = $document->extraction?->errors ?? [];
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-4 rounded-lg border border-slate-200 bg-white p-5 shadow-sm lg:flex-row lg:items-start lg:justify-between">
        <div>
            <a href="{{ route('source-documents.index') }}" class="text-sm font-semibold text-blue-700 hover:text-blue-800">Retour aux documents</a>
            <h1 class="mt-2 text-2xl font-black tracking-tight text-slate-950">{{ $document->original_name }}</h1>
            <div class="mt-3 flex flex-wrap gap-2 text-xs font-semibold">
                <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-slate-700">{{ $documentTypes[$document->document_type] ?? $document->document_type }}</span>
                <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-slate-700">{{ $tableaux[$document->tableau_code] ?? $document->tableau_code }}</span>
                <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-slate-700">Exercice {{ $document->exercice }}</span>
                <span class="rounded-full border px-2.5 py-1 {{ $statusClass }}">{{ $statuses[$document->status] ?? $document->status }}</span>
            </div>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row">
            <form method="POST" action="{{ route('source-documents.analyze', $document) }}">
                @csrf
                <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-800">Relancer l'analyse</button>
            </form>
            <form method="POST" action="{{ route('source-documents.destroy', $document) }}" onsubmit="return confirm('Supprimer ce document source ?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 transition hover:bg-rose-100">Supprimer</button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">{{ session('error') }}</div>
    @endif

    <section class="grid gap-4 md:grid-cols-4">
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Taille</p>
            <p class="mt-2 text-lg font-black text-slate-950">{{ number_format($document->size / 1024, 1, ',', ' ') }} Ko</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Importe le</p>
            <p class="mt-2 text-lg font-black text-slate-950">{{ optional($document->imported_at)->format('d/m/Y') }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Champs extraits</p>
            <p class="mt-2 text-lg font-black text-slate-950">{{ count($mappedData) }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Champs valides</p>
            <p class="mt-2 text-lg font-black text-slate-950">{{ $document->fieldSources->count() }}</p>
        </div>
    </section>

    @if(!empty($errors))
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            <p class="font-bold">Observations d'analyse</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach($errors as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-200 p-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700">Prevalidation</p>
                <h2 class="mt-1 text-lg font-black text-slate-950">Donnees extraites</h2>
            </div>
            <form method="POST" action="{{ route('source-documents.validate', $document) }}">
                @csrf
                <button type="submit" @disabled(count($mappedData) === 0) class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50">Valider les donnees</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-900 text-xs uppercase tracking-wide text-white">
                    <tr>
                        <th class="px-4 py-3 text-left">Cle proposee</th>
                        <th class="px-4 py-3 text-left">Position</th>
                        <th class="px-4 py-3 text-left">Valeur extraite</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse(array_slice($mappedData, 0, 80) as $field)
                        <tr class="hover:bg-blue-50/40">
                            <td class="px-4 py-3 font-mono text-xs font-bold text-slate-800">{{ $field['cle'] ?? '' }}</td>
                            <td class="px-4 py-3 text-slate-600">Ligne {{ $field['ligne'] ?? '-' }} - {{ $field['colonne'] ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-900">{{ $field['valeur'] ?? '' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-10 text-center text-sm text-slate-500">Aucune donnee extraite automatiquement pour ce document.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(count($mappedData) > 80)
            <div class="border-t border-slate-200 px-5 py-3 text-xs font-medium text-slate-500">Apercu limite aux 80 premiers champs extraits.</div>
        @endif
    </section>

    @if(!empty($rawData))
        <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-5">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700">Apercu brut</p>
                <h2 class="mt-1 text-lg font-black text-slate-950">Premieres lignes du fichier</h2>
            </div>
            <div class="overflow-x-auto p-5">
                <table class="min-w-full border-separate border-spacing-0 text-xs">
                    <tbody>
                        @foreach(array_slice($rawData, 0, 12) as $row)
                            <tr>
                                <th class="border border-slate-200 bg-slate-50 px-2 py-2 text-left font-mono text-slate-500">L{{ $row['row'] }}</th>
                                @foreach($row['cells'] as $value)
                                    <td class="border border-slate-200 px-2 py-2 text-slate-700">{{ $value }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>
@endsection
