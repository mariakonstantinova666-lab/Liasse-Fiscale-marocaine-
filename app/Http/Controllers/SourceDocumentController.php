<?php

namespace App\Http\Controllers;

use App\Models\LiasseFieldSource;
use App\Models\LiasseData;
use App\Models\BalanceItem;
use App\Models\SourceDocument;
use App\Models\Societe;
use App\Services\ActiveExerciceService;
use App\Services\DocumentExtractionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SourceDocumentController extends Controller
{
    private const DOCUMENT_TYPES = [
        'dossier_fiscal_complet' => 'Dossier fiscal complet',
        'immobilisations' => 'Etat detaille des immobilisations',
        'amortissements' => 'Tableau des amortissements',
        'capital' => 'Repartition du capital social',
        'emprunts' => 'Etat des emprunts',
        'provisions' => 'Detail des provisions',
        'associes' => 'Informations des associes',
        'stocks' => 'Etat detaille des stocks',
        'autre' => 'Autre document source',
    ];

    private const TABLEAUX = [
        'multi_tableaux' => 'Plusieurs tableaux detectes automatiquement',
        'immobilisations' => 'T04 - Immobilisations',
        'amortissements' => 'T08 - Amortissements',
        'provisions' => 'T09 - Provisions',
        'repartition_capital' => 'T13 - Repartition du capital',
        'interets_emprunts' => 'T18 - Interets des emprunts',
        'detail_stocks' => 'T20 - Detail des stocks',
        'tableau_financement' => 'T22 - Tableau de financement',
        'autre' => 'Autre tableau fiscal',
    ];

    public function index(Request $request, ActiveExerciceService $activeExercice)
    {
        $exercice = $activeExercice->current();
        $query = SourceDocument::query()
            ->with(['user', 'societe'])
            ->where('user_id', Auth::id())
            ->where('exercice', $exercice)
            ->latest();

        if ($request->filled('tableau_code')) {
            $query->where('tableau_code', $request->input('tableau_code'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return view('source_documents.index', [
            'documents' => $query->paginate(12)->withQueryString(),
            'documentTypes' => self::DOCUMENT_TYPES,
            'tableaux' => self::TABLEAUX,
            'statuses' => SourceDocument::statusLabels(),
            'filters' => $request->only(['tableau_code', 'status']),
            'exercice' => $exercice,
        ]);
    }

    public function create(ActiveExerciceService $activeExercice)
    {
        return view('source_documents.create', [
            'documentTypes' => self::DOCUMENT_TYPES,
            'tableaux' => self::TABLEAUX,
            'exercice' => $activeExercice->current(),
        ]);
    }

    public function store(
        Request $request,
        DocumentExtractionService $extractor,
        ActiveExerciceService $activeExercice
    )
    {
        $validated = $request->validate([
            'document' => 'required|file|mimes:xlsx,xls,csv,txt,pdf|max:10240',
            'document_type' => 'required|string|max:100',
            'tableau_code' => 'required|string|max:100',
        ]);
        $exercice = $activeExercice->current();

        $societe = Societe::where('user_id', Auth::id())->first();

        if (!$societe) {
            return redirect()->route('dashboard')->with('error', 'Configurez une societe avant d importer des documents sources.');
        }

        $file = $request->file('document');
        $path = $file->store("source-documents/{$societe->id}/{$exercice}", 'local');

        $document = SourceDocument::create([
            'user_id' => Auth::id(),
            'societe_id' => $societe->id,
            'exercice' => $exercice,
            'document_type' => $validated['document_type'],
            'tableau_code' => $validated['tableau_code'],
            'original_name' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize() ?: 0,
            'status' => SourceDocument::STATUS_IMPORTED,
            'imported_at' => now(),
        ]);

        $extraction = $extractor->extract($document);
        if ($extraction->status === SourceDocument::STATUS_ERROR) {
            return redirect()
                ->route('source-documents.show', $document)
                ->with('error', collect($extraction->errors)->filter()->implode(' ')
                    ?: 'Le document n a pas pu etre analyse. Aucune donnee n a ete importee.');
        }

        $mappedData = $this->enrichMappedData($document, $extraction->mapped_data ?? []);
        $extraction->update(['mapped_data' => $mappedData]);
        $appliedCount = $this->persistMappedData($document, $mappedData, 'needs_validation');

        return redirect()
            ->route('source-documents.show', $document)
            ->with('success', $appliedCount > 0
                ? "{$appliedCount} champ(s) importes dans les tableaux. Verifiez puis validez les donnees extraites."
                : 'Document source importe. Aucune correspondance directe avec les tableaux existants n a ete detectee.'
            );
    }

    public function show(SourceDocument $sourceDocument)
    {
        $this->authorizeDocument($sourceDocument);

        return view('source_documents.show', [
            'document' => $sourceDocument->load(['extraction', 'fieldSources']),
            'documentTypes' => self::DOCUMENT_TYPES,
            'tableaux' => self::TABLEAUX,
            'statuses' => SourceDocument::statusLabels(),
        ]);
    }

    public function analyze(SourceDocument $sourceDocument, DocumentExtractionService $extractor)
    {
        $this->authorizeDocument($sourceDocument);

        $extraction = $extractor->extract($sourceDocument);
        if ($extraction->status === SourceDocument::STATUS_ERROR) {
            return redirect()
                ->route('source-documents.show', $sourceDocument)
                ->with('error', collect($extraction->errors)->filter()->implode(' ')
                    ?: 'Le document n a pas pu etre analyse. Aucune donnee n a ete importee.');
        }

        $mappedData = $this->enrichMappedData($sourceDocument, $extraction->mapped_data ?? []);
        $extraction->update(['mapped_data' => $mappedData]);
        $appliedCount = $this->persistMappedData($sourceDocument, $mappedData, 'needs_validation');

        return redirect()
            ->route('source-documents.show', $sourceDocument)
            ->with('success', $appliedCount > 0
                ? "Analyse terminee : {$appliedCount} champ(s) importes dans les tableaux."
                : 'Analyse terminee, sans correspondance directe avec les tableaux existants.'
            );
    }

    public function validateExtraction(SourceDocument $sourceDocument)
    {
        $this->authorizeDocument($sourceDocument);

        $extraction = $sourceDocument->extraction;
        $mappedData = $this->enrichMappedData($sourceDocument, $extraction?->mapped_data ?? []);

        if (count($mappedData) === 0) {
            return redirect()
                ->route('source-documents.show', $sourceDocument)
                ->with('error', 'Aucune donnee extraite ne peut etre validee pour ce document.');
        }

        DB::transaction(function () use ($sourceDocument, $mappedData) {
            $this->persistMappedData($sourceDocument, $mappedData, 'validated');

            $sourceDocument->update(['status' => SourceDocument::STATUS_VALIDATED]);
            $sourceDocument->extraction?->update(['status' => SourceDocument::STATUS_VALIDATED]);
        });

        return redirect()
            ->route('source-documents.show', $sourceDocument)
            ->with('success', 'Donnees extraites validees et tracees.');
    }

    public function destroy(SourceDocument $sourceDocument)
    {
        $this->authorizeDocument($sourceDocument);

        Storage::disk('local')->delete($sourceDocument->stored_path);
        $sourceDocument->delete();

        return redirect()
            ->route('source-documents.index')
            ->with('success', 'Document source supprime.');
    }

    private function authorizeDocument(SourceDocument $document): void
    {
        abort_unless($document->user_id === Auth::id(), 403);
    }

    private function persistMappedData(SourceDocument $document, array $mappedData, string $status): int
    {
        $count = 0;
        $mappedData = $this->uniqueMappedData($mappedData);

        foreach ($mappedData as $field) {
            $tableauCode = (string) ($field['tableau_code'] ?? $document->tableau_code);
            $key = (string) ($field['cle'] ?? '');
            $value = (string) ($field['valeur'] ?? '');

            if ($tableauCode === '' || $key === '' || $value === '') {
                continue;
            }

            LiasseFieldSource::updateOrCreate(
                [
                    'societe_id' => $document->societe_id,
                    'exercice' => $document->exercice,
                    'tableau_code' => $tableauCode,
                    'cle' => $key,
                ],
                [
                    'user_id' => $document->user_id,
                    'source_document_id' => $document->id,
                    'valeur' => $value,
                    'source_type' => 'document',
                    'status' => $status,
                    'modified_by' => Auth::id(),
                    'validated_at' => $status === 'validated' ? now() : null,
                ]
            );

            LiasseData::updateOrCreate(
                [
                    'user_id' => $document->user_id,
                    'exercice' => $document->exercice,
                    'tableau_code' => $tableauCode,
                    'cle' => $key,
                ],
                ['valeur' => $value]
            );

            $count++;
        }

        return $count;
    }

    private function uniqueMappedData(array $mappedData): array
    {
        $unique = [];

        foreach ($mappedData as $field) {
            $tableauCode = (string) ($field['tableau_code'] ?? '');
            $key = (string) ($field['cle'] ?? '');

            if ($tableauCode === '' || $key === '') {
                continue;
            }

            $unique[$tableauCode.'|'.$key] = $field;
        }

        return array_values($unique);
    }

    private function enrichMappedData(SourceDocument $document, array $mappedData): array
    {
        return $this->enrichAffectationResultats($document, $mappedData);
    }

    private function enrichAffectationResultats(SourceDocument $document, array $mappedData): array
    {
        $resultat = $this->mappedNumber($mappedData, 'affectation_resultats', 'ligne4_montantA');

        if ($resultat <= 0) {
            return $mappedData;
        }

        $reportFinal = (float) BalanceItem::where('user_id', $document->user_id)
            ->where('exercice', $document->exercice)
            ->where('compte', 'like', '116%')
            ->get()
            ->sum(fn ($item) => (float) $item->solde_debiteur - (float) $item->solde_crediteur);

        if ($reportFinal <= 0) {
            return $mappedData;
        }

        $reportInitial = max(0, $reportFinal - $resultat);

        $this->setMappedField($mappedData, 'affectation_resultats', 'ligne2_montantA', $this->formatNumber($reportInitial));
        $this->setMappedField($mappedData, 'affectation_resultats', 'ligne6_montantB', $this->formatNumber($reportFinal));
        $this->setMappedField($mappedData, 'affectation_resultats', 'total_A', $this->formatNumber($reportInitial + $resultat));
        $this->setMappedField($mappedData, 'affectation_resultats', 'total_B', $this->formatNumber($reportFinal));

        return $mappedData;
    }

    private function mappedNumber(array $mappedData, string $tableauCode, string $key): float
    {
        foreach ($mappedData as $field) {
            if (($field['tableau_code'] ?? '') === $tableauCode && ($field['cle'] ?? '') === $key) {
                $value = str_replace(["\xc2\xa0", ' '], '', (string) ($field['valeur'] ?? ''));
                $value = str_replace(',', '.', $value);

                return is_numeric($value) ? (float) $value : 0.0;
            }
        }

        return 0.0;
    }

    private function setMappedField(array &$mappedData, string $tableauCode, string $key, string $value): void
    {
        foreach ($mappedData as &$field) {
            if (($field['tableau_code'] ?? '') === $tableauCode && ($field['cle'] ?? '') === $key) {
                $field['valeur'] = $value;
                return;
            }
        }
        unset($field);

        $mappedData[] = [
            'tableau_code' => $tableauCode,
            'cle' => $key,
            'valeur' => $value,
            'ligne' => null,
            'colonne' => null,
        ];
    }

    private function formatNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
