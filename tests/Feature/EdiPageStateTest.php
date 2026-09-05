<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ActiveExerciceService;
use App\Services\EdiXmlGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class EdiPageStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_generation_is_authorized_without_current_blocker_or_previous_error(): void
    {
        $response = $this->getEdiPage();

        $response->assertOk()
            ->assertSee('Génération autorisée')
            ->assertDontSee('Génération bloquée');
        $this->assertGenerateButtonDisabled($response, false);
    }

    public function test_current_blocker_blocks_generation_and_disables_button(): void
    {
        $response = $this->getEdiPage([$this->control('CURRENT_BLOCKER', true, 'Blocage actuel')]);

        $response->assertOk()
            ->assertSee('Génération bloquée')
            ->assertSee('Blocage actuel');
        $this->assertGenerateButtonDisabled($response, true);
    }

    public function test_previous_generation_error_is_shown_without_blocking_current_generation(): void
    {
        $response = $this->getEdiPage([], [$this->control('PREVIOUS_ERROR', true, 'Erreur historique')]);

        $response->assertOk()
            ->assertSee('Génération autorisée')
            ->assertDontSee('Génération bloquée')
            ->assertSee('Erreurs de la dernière tentative')
            ->assertSee('Erreur historique — détail');
        $this->assertGenerateButtonDisabled($response, false);
    }

    public function test_current_blocker_and_previous_error_are_displayed_separately(): void
    {
        $response = $this->getEdiPage(
            [$this->control('CURRENT_BLOCKER', true, 'Blocage actuel')],
            [$this->control('PREVIOUS_ERROR', true, 'Erreur historique')]
        );

        $response->assertOk()
            ->assertSee('Génération bloquée')
            ->assertSee('Blocage actuel')
            ->assertSee('Erreurs de la dernière tentative')
            ->assertSee('Erreur historique — détail');
        $this->assertGenerateButtonDisabled($response, true);
    }

    public function test_warnings_do_not_block_generation(): void
    {
        $response = $this->getEdiPage([$this->control('WARNING', false, 'Avertissement actuel')]);

        $response->assertOk()
            ->assertSee('Génération autorisée')
            ->assertDontSee('Génération bloquée')
            ->assertSee('Avertissements')
            ->assertSee('1 avertissement(s) non bloquant(s)');
        $this->assertGenerateButtonDisabled($response, false);
    }

    public function test_retry_remains_available_after_a_previous_error_when_current_context_is_valid(): void
    {
        $response = $this->getEdiPage([], [$this->control('RETRYABLE_ERROR', true, 'Erreur corrigée')]);

        $response->assertOk()
            ->assertSee('Génération autorisée')
            ->assertDontSee('Génération bloquée')
            ->assertSee('Erreurs de la dernière tentative')
            ->assertSee('Erreur corrigée — détail');
        $this->assertGenerateButtonDisabled($response, false);
    }

    private function getEdiPage(array $controls = [], array $generationErrors = []): TestResponse
    {
        $user = User::factory()->create();

        $this->mock(ActiveExerciceService::class)
            ->shouldReceive('current')
            ->twice()
            ->andReturn(2026);
        app(ActiveExerciceService::class)
            ->shouldReceive('available')
            ->once()
            ->andReturn([2026]);

        $this->mock(EdiXmlGeneratorService::class)
            ->shouldReceive('context')
            ->once()
            ->with($user->id, 2026)
            ->andReturn([
                'societe' => null,
                'items' => collect(),
                'itemsPrev' => collect(),
                'liasseData' => collect(),
                'controls' => $controls,
            ]);

        return $this->actingAs($user)
            ->withSession(['edi_blocking_errors' => $generationErrors])
            ->get(route('liasse.edi.index'));
    }

    private function control(string $id, bool $blocking, string $title): array
    {
        return [
            'id' => $id,
            'titre' => $title,
            'ok' => false,
            'ecart' => 1,
            'message' => $title.' — détail',
            'bloquant' => $blocking,
            'severity' => $blocking ? 'Erreur' : 'Avertissement',
            'tableau' => 'EDI',
            'rubrique' => 'Test',
            'regle' => 'Règle de test',
            'suggestion' => 'Suggestion de test',
        ];
    }

    private function assertGenerateButtonDisabled(TestResponse $response, bool $expected): void
    {
        preg_match('/<button\b[^>]*\bid="edi-submit"[^>]*>/s', $response->getContent(), $matches);
        $this->assertNotEmpty($matches, 'Le bouton de génération EDI est introuvable.');

        $isDisabled = preg_match('/\bdisabled(?:\s*=|\s|>)/', $matches[0]) === 1;
        $this->assertSame($expected, $isDisabled, $matches[0]);
    }
}
