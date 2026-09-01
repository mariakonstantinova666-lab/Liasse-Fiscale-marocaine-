<?php

namespace Tests\Feature;

use App\Models\LiasseData;
use App\Models\User;
use App\Services\DocumentExtractionService;
use App\Services\EdiXmlGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use ReflectionMethod;
use Tests\TestCase;

class LocationsBauxTest extends TestCase
{
    use RefreshDatabase;

    public function test_v4_extracts_owner_if_and_cin_without_inventing_missing_t19_fields(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['Bien loué', 'Propriétaire/Bailleur', 'Adresse propriétaire', 'N° IF/CIN propriétaire', 'Date contrat', 'Loyer annuel (MAD)'],
            ['Bureaux et locaux commerciaux', 'TAZI Abdelkader', '12 Rue Mohammed V, Tanger', 'IF 18452399 / CIN AB556214', '01/01/2024', 52800],
        ]);

        $fields = $this->mapLocationsBaux($sheet);

        $this->assertSame('18452399', $fields['r0_c6']);
        $this->assertSame('AB556214', $fields['r0_c7']);
        $this->assertSame('52800', $fields['r0_c10']);
        $this->assertArrayNotHasKey('r0_c2', $fields);
        $this->assertArrayNotHasKey('r0_c8', $fields);
        $this->assertArrayNotHasKey('r0_c11', $fields);
        $this->assertArrayNotHasKey('r0_c12', $fields);
        $this->assertArrayNotHasKey('r0_c13', $fields);
    }

    public function test_t19_view_displays_eighteen_rows_and_existing_amount_totals(): void
    {
        $user = User::factory()->create();

        foreach (['r0_c10' => '52800', 'r0_c11' => '52 800,00'] as $key => $value) {
            LiasseData::create([
                'user_id' => $user->id,
                'exercice' => 2026,
                'tableau_code' => 'locations_baux',
                'cle' => $key,
                'valeur' => $value,
            ]);
        }

        $response = $this->actingAs($user)
            ->withSession(['annee_exercice' => 2026])
            ->get(route('liasse.locations_baux'));

        $response->assertOk();
        $this->assertSame(18 * 13, substr_count($response->getContent(), 'name="f[r'));
        $response->assertSee('name="f[r0_c1]"', false);
        $response->assertSee('name="f[r17_c13]"', false);
        $this->assertSame(2, substr_count($response->getContent(), 'font-mono">52 800,00</td>'));
    }

    public function test_persisted_owner_if_and_cin_use_existing_t19_edi_mappings(): void
    {
        $this->assertSame(28, config('edi.table_ids.locations_baux'));
        $this->assertSame(14040, config('edi.dynamic_rows.locations_baux.r.c6'));
        $this->assertSame(14041, config('edi.dynamic_rows.locations_baux.r.c7'));

        $user = User::factory()->create();

        foreach ([
            'r0_c2' => 'Tanger, Avenue de la Paix',
            'r0_c6' => '18452399',
            'r0_c7' => 'AB556214',
            'r0_c10' => '52800',
            'r0_c11' => '52800',
        ] as $key => $value) {
            LiasseData::create([
                'user_id' => $user->id,
                'exercice' => 2026,
                'tableau_code' => 'locations_baux',
                'cle' => $key,
                'valeur' => $value,
            ]);
        }

        $service = app(EdiXmlGeneratorService::class);
        $method = new ReflectionMethod($service, 'buildMappedValues');
        $values = $method->invoke($service, $service->context($user->id, 2026));
        $t19 = collect($values)->where('tableau', 28)->keyBy('code');

        $this->assertSame('Tanger, Avenue de la Paix', $t19->get(1268)['valeur']);
        $this->assertSame('18452399', $t19->get(14040)['valeur']);
        $this->assertSame('AB556214', $t19->get(14041)['valeur']);
        $this->assertSame('52800.00', $t19->get(1271)['valeur']);
        $this->assertSame('52800.00', $t19->get(1272)['valeur']);
        $this->assertSame('52800.00', $t19->get(1279)['valeur']);
        $this->assertSame('52800.00', $t19->get(1280)['valeur']);
        $this->assertSame(1, $t19->get(14040)['ligne']);
        $this->assertSame(1, $t19->get(14041)['ligne']);
    }

    /**
     * @return array<string, string>
     */
    private function mapLocationsBaux($sheet): array
    {
        $service = app(DocumentExtractionService::class);
        $method = new ReflectionMethod($service, 'mapLocationsBaux');

        return collect($method->invoke($service, $sheet))
            ->pluck('valeur', 'cle')
            ->all();
    }
}
