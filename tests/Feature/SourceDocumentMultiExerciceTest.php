<?php

namespace Tests\Feature;

use App\Models\BalanceItem;
use App\Models\LiasseData;
use App\Models\Societe;
use App\Models\SourceDocument;
use App\Models\User;
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
            ->post(route('source-documents.store'), $this->uploadPayload($this->workbook(2025)))
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

    private function workbook(?int $exercice, string $originalName = 'dossier.xlsx'): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $fiche = $spreadsheet->getActiveSheet();
        $fiche->setTitle('Fiche société');
        $fiche->setCellValue('A1', 'Montant du capital social');
        $fiche->setCellValue('B1', 1000);

        $registre = $spreadsheet->createSheet();
        $registre->setTitle('Registre des immobilisations');

        $decision = $spreadsheet->createSheet();
        $decision->setTitle('Décision AG');
        $decision->setCellValue('A1', $exercice === null
            ? "Résultat net de l'exercice (perte)"
            : "Résultat net de l'exercice {$exercice} (perte)");
        $decision->setCellValue('B1', 300);

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
