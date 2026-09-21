@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between dark:border-slate-700 dark:bg-slate-900">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700 dark:text-blue-300">Documents sources</p>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-slate-100">Pieces complementaires de la liasse</h1>
            <p class="mt-2 max-w-3xl text-sm text-slate-600 dark:text-slate-300">Exercice {{ $exercice }} — centralisez les etats externes necessaires aux tableaux fiscaux, suivez leur traitement et preparez la validation des donnees extraites.</p>
        </div>
        <a href="{{ route('source-documents.create') }}" class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800">
            Importer un document
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
            {{ session('error') }}
        </div>
    @endif

    <form method="GET" action="{{ route('source-documents.index') }}" class="grid gap-4 rounded-lg border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-3 md:items-end dark:border-slate-700 dark:bg-slate-900">
        <div>
            <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">Portee fiscale</label>
            <select name="tableau_code" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600 dark:border-slate-600 dark:focus:border-blue-500 dark:focus:ring-blue-500 dark:bg-slate-800 dark:text-slate-100">
                <option value="">Toutes les portees</option>
                @foreach($tableaux as $code => $label)
                    <option value="{{ $code }}" @selected(($filters['tableau_code'] ?? '') === $code)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">Statut</label>
            <select name="status" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600 dark:border-slate-600 dark:focus:border-blue-500 dark:focus:ring-blue-500 dark:bg-slate-800 dark:text-slate-100">
                <option value="">Tous les statuts</option>
                @foreach($statuses as $code => $label)
                    <option value="{{ $code }}" @selected(($filters['status'] ?? '') === $code)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="inline-flex flex-1 items-center justify-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-blue-600 dark:hover:bg-blue-500">Filtrer</button>
            <a href="{{ route('source-documents.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">Reset</a>
        </div>
    </form>

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                <thead class="bg-slate-900 text-xs uppercase tracking-wide text-white">
                    <tr>
                        <th class="px-4 py-3 text-left">Document</th>
                        <th class="px-4 py-3 text-left">Type</th>
                        <th class="px-4 py-3 text-left">Exercice</th>
                        <th class="px-4 py-3 text-left">Portee</th>
                        <th class="px-4 py-3 text-left">Statut</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($documents as $document)
                        @php
                            $statusClass = match ($document->status) {
                                'validated' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300',
                                'needs_validation' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300',
                                'error' => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300',
                                'analyzing' => 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-300',
                                default => 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300',
                            };
                        @endphp
                        <tr class="hover:bg-blue-50/40 dark:hover:bg-blue-500/10">
                            <td class="px-4 py-3">
                                <a href="{{ route('source-documents.show', $document) }}" class="font-bold text-slate-900 hover:text-blue-700 dark:text-slate-100 dark:hover:text-blue-300">{{ $document->original_name }}</a>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Importe le {{ optional($document->imported_at)->format('d/m/Y H:i') }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-700 dark:text-slate-200">{{ $documentTypes[$document->document_type] ?? $document->document_type }}</td>
                            <td class="px-4 py-3 font-mono font-semibold text-slate-800 dark:text-slate-200">{{ $document->exercice }}</td>
                            <td class="px-4 py-3 text-slate-700 dark:text-slate-200">{{ $tableaux[$document->tableau_code] ?? $document->tableau_code }}</td>
                            <td class="px-4 py-3"><span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-bold {{ $statusClass }}">{{ $statuses[$document->status] ?? $document->status }}</span></td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('source-documents.show', $document) }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-800 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-blue-500 dark:hover:bg-slate-800 dark:hover:text-blue-200">Ouvrir</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <p class="font-semibold text-slate-700 dark:text-slate-200">Aucun document source importe.</p>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Ajoutez les etats externes necessaires aux tableaux declaratifs.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 px-4 py-3 dark:border-slate-700">
            {{ $documents->links() }}
        </div>
    </div>
</div>
@endsection
