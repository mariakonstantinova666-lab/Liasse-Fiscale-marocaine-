<?php

namespace Tests\Feature;

use App\Models\BalanceItem;
use App\Models\LiasseData;
use App\Models\LiasseFieldSource;
use App\Models\Societe;
use App\Models\SourceDocument;
use App\Models\User;
use App\Services\DocumentExtractionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class SourceDocumentMultiExerciceTest extends TestCase
{
    use RefreshDatabase;

    /** @var string[] */
    private array $temporaryFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }

    public function test_upload_uses_active_exercice_and_dynamic_t14_mapping_for_2025_2026_and_2027(): void
    {
        [$user, $societe] = $this->userAndSociete();

        foreach ([2025, 2026, 2027] as $exercice) {
            $this->balance($user, $societe, $exercice, "BALANCE-{$exercice}");

            $this->actingAs($user)
                ->withSession(['annee_exercice' => $exercice])
                ->post(route('source-documents.store'), $this->uploadPayload(
                    $this->workbook($exercice, "dossier-{$exercice}.xlsx")
                ))
                ->assertRedirect();

            $this->assertDatabaseHas('source_documents', [
                'user_id' => $user->id,
                'societe_id' => $societe->id,
                'exercice' => $exercice,
                'original_name' => "dossier-{$exercice}.xlsx",
            ]);
            $this->assertDatabaseHas('liasse_data', [
                'user_id' => $user->id,
                'exercice' => $exercice,
                'tableau_code' => 'affectation_resultats',
                'cle' => 'ligne4_montantA',
                'valeur' => '300',
            ]);
        }

        $this->assertSame([2025, 2026, 2027], SourceDocument::query()->orderBy('exercice')->pluck('exercice')->all());
    }

    public function test_posted_exercice_cannot_override_active_context(): void
    {
        [$user, $societe] = $this->userAndSociete();
        $this->balance($user, $societe, 2026, 'BALANCE-2026');

        $payload = $this->uploadPayload($this->workbook(2026));
        $payload['exercice'] = 2025;

        $this->actingAs($user)
            ->withSession(['annee_exercice' => 2026])
            ->post(route('source-documents.store'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('source_documents', ['exercice' => 2026]);
        $this->assertDatabaseMissing('source_documents', ['exercice' => 2025]);
    }

    public function test_create_form_displays_active_context_without_editable_or_fixed_year_fallback(): void
    {
        [$user, $societe] = $this->userAndSociete();
        $this->balance($user, $societe, 2025, 'BALANCE-2025');

        $this->actingAs($user)
            ->withSession(['annee_exercice' => 2025])
            ->get(route('source-documents.create'))
            ->assertOk()
            ->assertSee('Exercice : 2025');

        $source = file_get_contents(resource_path('views/source_documents/create.blade.php'));
        $this->assertStringNotContainsString('name="exercice"', $source);
        $this->assertStringNotContainsString("session('annee_exercice', 2025)", $source);
        $this->assertStringNotContainsString("session('annee_exercice', 2026)", $source);
    }

    public function test_reanalysis_uses_document_exercice_after_active_session_changes(): void
    {
        [$user, $societe] = $this->userAndSociete();
        $this->balance($user, $societe, 2025, 'BALANCE-2025');
        $this->balance($user, $societe, 2026, 'BALANCE-2026');

        $this->actingAs($user)
            ->withSession(['annee_exercice' => 2025])
            ->post(route('source-documents.store'), $this->uploadPayload($this->workbook(2025, affectationExercice: 2024)))
            ->assertRedirect();

        $document = SourceDocument::query()->firstOrFail();
        LiasseData::query()
            ->where('user_id', $user->id)
            ->where('exercice', 2025)
            ->where('tableau_code', 'affectation_resultats')
            ->where('cle', 'ligne4_montantA')
            ->update(['valeur' => '1']);

        $this->withSession(['annee_exercice' => 2026])
            ->post(route('source-documents.analyze', $document))
            ->assertRedirect();

        $this->assertSame(2025, $document->fresh()->exercice);
        $this->assertDatabaseHas('liasse_data', [
            'user_id' => $user->id,
            'exercice' => 2025,
            'tableau_code' => 'affectation_resultats',
            'cle' => 'ligne4_montantA',
            'valeur' => '300',
        ]);
        $this->assertDatabaseMissing('liasse_data', [
            'user_id' => $user->id,
            'exercice' => 2026,
            'tableau_code' => 'affectation_resultats',
            'cle' => 'ligne4_montantA',
        ]);
    }

    public function test_reliable_year_mismatch_is_rejected_without_liasse_write(): void
    {
        [$user, $societe] = $this->userAndSociete();
        $this->balance($user, $societe, 2026, 'BALANCE-2026');

        $this->actingAs($user)
            ->withSession(['annee_exercice' => 2026])
            ->post(route('source-documents.store'), $this->uploadPayload($this->workbook(2025)))
            ->assertRedirect()
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'exercice 2025')
                && str_contains($message, 'exercice 2026'));

        $document = SourceDocument::query()->firstOrFail();
        $this->assertSame(2026, $document->exercice);
        $this->assertSame(SourceDocument::STATUS_ERROR, $document->status);
        $this->assertSame([], $document->extraction->mapped_data);
        $this->assertStringContainsString('exercice 2025', $document->extraction->errors[0]);
        $this->assertStringContainsString('exercice 2026', $document->extraction->errors[0]);
        $this->assertDatabaseMissing('liasse_data', ['user_id' => $user->id, 'exercice' => 2026]);
        $this->assertDatabaseCount('liasse_field_sources', 0);
    }

    public function test_current_dossier_accepts_previous_year_ag_and_historical_references(): void
    {
        [$user, $societe] = $this->userAndSociete();
        $this->balance($user, $societe, 2026, 'BALANCE-2026');
        $file = $this->workbook(2026, 'nom-trompeur-2025.xlsx', 2025, function ($book) {
            $book->getSheetByName('Fiche société')->setCellValue('A5', 'Exercice antérieur')->setCellValue('B5', 2025);
            $book->getSheetByName('Registre des immobilisations')->setCellValue('B2', '01/06/2025');
            $comparatif = $book->createSheet()->setTitle('Comparatif N N-1');
            $comparatif->fromArray([['EXERCICE 2025', 'EXERCICE 2026'], [100, 200]]);
        });

        $this->actingAs($user)->withSession(['annee_exercice' => 2026])
            ->post(route('source-documents.store'), $this->uploadPayload($file))
            ->assertRedirect()->assertSessionHasNoErrors()->assertSessionMissing('error');

        $document = SourceDocument::query()->firstOrFail();
        $this->assertSame(SourceDocument::STATUS_NEEDS_VALIDATION, $document->status);
        $this->assertSame([], $document->extraction->errors);
        $this->assertNotEmpty($document->extraction->mapped_data);
        $this->assertDatabaseHas('liasse_data', [
            'user_id' => $user->id, 'exercice' => 2026,
            'tableau_code' => 'affectation_resultats', 'cle' => 'ligne4_montantA', 'valeur' => '300',
        ]);
        $this->assertDatabaseHas('liasse_field_sources', [
            'source_document_id' => $document->id, 'exercice' => 2026,
            'tableau_code' => 'affectation_resultats', 'cle' => 'ligne4_montantA', 'valeur' => '300',
        ]);
        $this->assertDatabaseMissing('liasse_data', ['user_id' => $user->id, 'exercice' => 2025]);
    }

    public function test_ag_year_alone_does_not_define_the_dossier_year(): void
    {
        [$user, $societe] = $this->userAndSociete();
        $this->balance($user, $societe, 2026, 'BALANCE-2026');
        $this->actingAs($user)->withSession(['annee_exercice' => 2026])
            ->post(route('source-documents.store'), $this->uploadPayload($this->workbook(null, affectationExercice: 2025)))
            ->assertRedirect()->assertSessionMissing('error');
        $this->assertSame(SourceDocument::STATUS_NEEDS_VALIDATION, SourceDocument::query()->firstOrFail()->status);
        $this->assertDatabaseHas('liasse_data', [
            'exercice' => 2026, 'tableau_code' => 'affectation_resultats',
            'cle' => 'ligne4_montantA', 'valeur' => '300',
        ]);
    }

    public function test_title_or_period_mismatch_preserves_existing_data_and_provenance(): void
    {
        [$user, $societe] = $this->userAndSociete();
        $this->balance($user, $societe, 2026, 'BALANCE-2026');
        $this->actingAs($user)->withSession(['annee_exercice' => 2026])
            ->post(route('source-documents.store'), $this->uploadPayload($this->workbook(2026)))
            ->assertSessionMissing('error');
        $dataBefore = LiasseData::query()->orderBy('id')->get()->toArray();
        $sourcesBefore = LiasseFieldSource::query()->orderBy('id')->get()->toArray();

        foreach (['title', 'period'] as $evidence) {
            $file = $this->workbook(2025, 'dossier-2026.xlsx', 2026, function ($book) use ($evidence) {
                if ($evidence === 'title') {
                    $book->getSheetByName('Fiche société')->setCellValue('B3', null);
                } else {
                    $book->getSheetByName('Registre des immobilisations')->setCellValue('A1', null);
                }
            });
            $this->post(route('source-documents.store'), $this->uploadPayload($file))
                ->assertRedirect()->assertSessionHas('error', fn ($message) => str_contains($message, 'exercice 2025'));
            $document = SourceDocument::query()->latest('id')->firstOrFail();
            $this->assertSame(SourceDocument::STATUS_ERROR, $document->status);
            $this->assertSame([], $document->extraction->mapped_data);
            $this->assertSame($dataBefore, LiasseData::query()->orderBy('id')->get()->toArray());
            $this->assertSame($sourcesBefore, LiasseFieldSource::query()->orderBy('id')->get()->toArray());
        }
    }

    public function test_conflicting_principal_years_are_rejected_before_fiscal_writes(): void
    {
        [$user, $societe] = $this->userAndSociete();
        $this->balance($user, $societe, 2026, 'BALANCE-2026');
        $file = $this->workbook(2026, customize: function ($book) {
            $book->getSheetByName('Fiche société')->setCellValue('B3', 'Du 01/01/2025 au 31/12/2025');
        });
        $this->actingAs($user)->withSession(['annee_exercice' => 2026])
            ->post(route('source-documents.store'), $this->uploadPayload($file))
            ->assertRedirect()->assertSessionHas('error', fn ($message) => str_contains($message, 'contradictoires'));
        $this->assertDatabaseCount('liasse_data', 0);
        $this->assertDatabaseCount('liasse_field_sources', 0);
    }

    public function test_real_2026_dossier_extracts_previous_year_result_with_provenance(): void
    {
        [$user, $societe] = $this->userAndSociete();
        $this->balance($user, $societe, 2026, 'BALANCE-2026');
        $name = 'Dossier_Fiscal_D3Soft_2026_2 (2).xlsx';
        $file = UploadedFile::fake()->createWithContent($name, file_get_contents(base_path('docs/'.$name)));
        $this->actingAs($user)->withSession(['annee_exercice' => 2026])
            ->post(route('source-documents.store'), $this->uploadPayload($file))
            ->assertRedirect()->assertSessionHasNoErrors()->assertSessionMissing('error');
        $document = SourceDocument::query()->firstOrFail();
        $this->assertSame(SourceDocument::STATUS_NEEDS_VALIDATION, $document->status);
        $this->assertSame([], $document->extraction->errors);
        $this->assertNotEmpty($document->extraction->mapped_data);
        foreach (['ligne4_montantA', 'total_A'] as $key) {
            $this->assertDatabaseHas('liasse_data', [
                'user_id' => $user->id, 'exercice' => 2026,
                'tableau_code' => 'affectation_resultats', 'cle' => $key, 'valeur' => '38000',
            ]);
            $this->assertDatabaseHas('liasse_field_sources', [
                'source_document_id' => $document->id, 'exercice' => 2026,
                'tableau_code' => 'affectation_resultats', 'cle' => $key, 'valeur' => '38000',
            ]);
        }
        $this->assertDatabaseMissing('liasse_data', ['user_id' => $user->id, 'exercice' => 2025]);
    }

    public function test_period_detection_uses_valid_closing_year_and_ignores_invalid_periods(): void
    {
        $service = new DocumentExtractionService();
        $detect = new \ReflectionMethod($service, 'detectDossierExercice');
        foreach ([
            'Du 01/07/2025 au 30/06/2026' => 2026,
            'Du 01/01/2026 au 31/12/2026' => 2026,
            'Du 01/01/2026 au 31/02/2026' => null,
            'Du 01/01/2026 au 31/12/2025' => null,
        ] as $period => $expected) {
            $book = new Spreadsheet();
            $fiche = $book->getActiveSheet()->setTitle('Fiche société');
            $fiche->setCellValue('A1', 'Exercice social')->setCellValue('B1', $period);
            $this->assertSame($expected, $detect->invoke($service, ['Fiche société' => $fiche]), $period);
            $book->disconnectWorksheets();
        }
    }

    public function test_absence_of_reliable_year_does_not_block_extraction(): void
    {
        [$user, $societe] = $this->userAndSociete();
        $this->balance($user, $societe, 2026, 'BALANCE-2026');

        $this->actingAs($user)
            ->withSession(['annee_exercice' => 2026])
            ->post(route('source-documents.store'), $this->uploadPayload($this->workbook(null)))
            ->assertRedirect();

        $document = SourceDocument::query()->firstOrFail();
        $this->assertSame(SourceDocument::STATUS_NEEDS_VALIDATION, $document->status);
        $this->assertNotEmpty($document->extraction->mapped_data);
        $this->assertDatabaseHas('liasse_data', [
            'user_id' => $user->id,
            'exercice' => 2026,
            'tableau_code' => 'repartition_capital',
            'cle' => 'montant_capital',
            'valeur' => '1000',
        ]);
    }

    public function test_index_is_scoped_to_active_exercice(): void
    {
        [$user, $societe] = $this->userAndSociete();
        $this->balance($user, $societe, 2025, 'BALANCE-2025');
        $this->balance($user, $societe, 2026, 'BALANCE-2026');
        $this->document($user, $societe, 2025, 'document-2025.xlsx');
        $this->document($user, $societe, 2026, 'document-2026.xlsx');

        $this->actingAs($user)
            ->withSession(['annee_exercice' => 2025])
            ->get(route('source-documents.index'))
            ->assertOk()
            ->assertSee('document-2025.xlsx')
            ->assertDontSee('document-2026.xlsx')
            ->assertSee('Exercice 2025');
    }

    public function test_same_original_name_coexists_across_exercices_and_balances_stay_intact(): void
    {
        [$user, $societe] = $this->userAndSociete();
        $this->balance($user, $societe, 2025, 'BALANCE-2025');
        $this->balance($user, $societe, 2026, 'BALANCE-2026');
        $balancesBefore = BalanceItem::query()->orderBy('id')->get()->toArray();

        foreach ([2025, 2026] as $exercice) {
            $this->actingAs($user)
                ->withSession(['annee_exercice' => $exercice])
                ->post(route('source-documents.store'), $this->uploadPayload(
                    $this->workbook($exercice, 'dossier-fiscal.xlsx')
                ))
                ->assertRedirect();
        }

        $documents = SourceDocument::query()->orderBy('exercice')->get();
        $this->assertCount(2, $documents);
        $this->assertSame(['dossier-fiscal.xlsx', 'dossier-fiscal.xlsx'], $documents->pluck('original_name')->all());
        $this->assertNotSame($documents[0]->stored_path, $documents[1]->stored_path);
        $this->assertStringContainsString('/2025/', str_replace('\\', '/', $documents[0]->stored_path));
        $this->assertStringContainsString('/2026/', str_replace('\\', '/', $documents[1]->stored_path));
        Storage::disk('local')->assertExists($documents[0]->stored_path);
        Storage::disk('local')->assertExists($documents[1]->stored_path);
        $this->assertSame($balancesBefore, BalanceItem::query()->orderBy('id')->get()->toArray());
    }

    /** @return array{User, Societe} */
    private function userAndSociete(): array
    {
        $user = User::factory()->create();
        $societe = Societe::create([
            'user_id' => $user->id,
            'nom_societe' => 'Société documentaire',
        ]);

        return [$user, $societe];
    }

    private function balance(User $user, Societe $societe, int $exercice, string $compte): void
    {
        BalanceItem::create([
            'user_id' => $user->id,
            'societe_id' => $societe->id,
            'compte' => $compte,
            'libelle' => "Balance {$exercice}",
            'solde_debiteur' => 1,
            'solde_crediteur' => 0,
            'exercice' => $exercice,
        ]);
    }

    private function document(User $user, Societe $societe, int $exercice, string $name): SourceDocument
    {
        return SourceDocument::create([
            'user_id' => $user->id,
            'societe_id' => $societe->id,
            'exercice' => $exercice,
            'document_type' => 'dossier_fiscal_complet',
            'tableau_code' => 'multi_tableaux',
            'original_name' => $name,
            'stored_path' => "source-documents/{$societe->id}/{$exercice}/{$name}",
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'size' => 1,
            'status' => SourceDocument::STATUS_IMPORTED,
            'imported_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    private function uploadPayload(UploadedFile $file): array
    {
        return [
            'document' => $file,
            'document_type' => 'dossier_fiscal_complet',
            'tableau_code' => 'multi_tableaux',
        ];
    }

    private function workbook(?int $exercice, string $originalName = 'dossier.xlsx', ?int $affectationExercice = null, ?\Closure $customize = null): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $fiche = $spreadsheet->getActiveSheet();
        $fiche->setTitle('Fiche société');
        $fiche->setCellValue('A1', 'Montant du capital social');
        $fiche->setCellValue('B1', 1000);

        $registre = $spreadsheet->createSheet();
        $registre->setTitle('Registre des immobilisations');
        if ($exercice !== null) {
            $registre->setCellValue('A1', "REGISTRE DES IMMOBILISATIONS — EXERCICE {$exercice}");
            $fiche->setCellValue('A3', 'Exercice social');
            $fiche->setCellValue('B3', "Du 01/01/{$exercice} au 31/12/{$exercice}");
        }

        $decision = $spreadsheet->createSheet();
        $decision->setTitle('Décision AG');
        $affectationExercice ??= $exercice;
        $decision->setCellValue('A1', $affectationExercice === null
            ? "Résultat net de l'exercice (perte)"
            : "Résultat net de l'exercice {$affectationExercice} (perte)");
        $decision->setCellValue('B1', 300);
        if ($customize !== null) {
            $customize($spreadsheet);
        }

        $path = tempnam(sys_get_temp_dir(), 'source-document-');
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();
        $this->temporaryFiles[] = $path;

        return new UploadedFile(
            $path,
            $originalName,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }
}
