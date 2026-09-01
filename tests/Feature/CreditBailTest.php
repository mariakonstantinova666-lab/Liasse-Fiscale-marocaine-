<?php

namespace Tests\Feature;

use App\Models\LiasseData;
use App\Models\User;
use App\Services\EdiXmlGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

class CreditBailTest extends TestCase
{
    use RefreshDatabase;

    public function test_view_displays_eighteen_rows_and_read_only_official_totals(): void
    {
        $user = User::factory()->create();
        $this->storeFields($user, [
            'r0_c4' => '52800',
            'r1_c4' => '52 800,50',
            'r0_c6' => '40000',
            'r0_c7' => '30 000',
            'r0_c8' => '20000.50',
            'r0_c9' => '30 000,50',
            'r0_c10' => '1000',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['annee_exercice' => 2026])
            ->get(route('liasse.credit_bail'));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertSame(18 * 11, substr_count($content, 'name="f[r'));
        $response->assertSee('name="f[r0_c1]"', false);
        $response->assertSee('name="f[r17_c11]"', false);
        $this->assertStringNotContainsString('name="f[total_', $content);
        $response->assertSee('105 600,50', false);
        $response->assertSee('40 000,00', false);
        $response->assertSee('30 000,00', false);
        $response->assertSee('20 000,50', false);
        $response->assertSee('30 000,50', false);
        $response->assertSee('1 000,00', false);
        $this->assertSame(2, substr_count($content, 'text-center">-</td>'));
    }

    public function test_first_and_eighteenth_rows_use_all_official_dynamic_codes_and_types(): void
    {
        $this->seedCreditBailCatalog();
        $user = User::factory()->create();
        $this->storeFields($user, [
            'r0_c1' => 'Matériel informatique',
            'r0_c2' => '01/01/2026',
            'r0_c3' => '36',
            'r0_c4' => '120000',
            'r0_c5' => '5',
            'r0_c6' => '40000',
            'r0_c7' => '30000',
            'r0_c8' => '20000',
            'r0_c9' => '30000',
            'r0_c10' => '1000',
            'r0_c11' => 'Contrat test',
            'r17_c1' => 'Véhicule test',
            'r17_c3' => '60',
            'r17_c4' => '200000',
        ]);

        $values = $this->mappedCreditBailValues($user);
        $line1 = $values->where('ligne', 1)->keyBy('code');
        $line18 = $values->where('ligne', 18)->keyBy('code');

        $this->assertSame([
            1098 => 'Matériel informatique',
            1099 => '01/01/2026',
            1100 => '36',
            1101 => '120000.00',
            1102 => '5.00',
            1103 => '40000.00',
            1104 => '30000.00',
            1105 => '20000.00',
            1106 => '30000.00',
            1107 => '1000.00',
            1108 => 'Contrat test',
        ], $line1->mapWithKeys(fn ($cell) => [$cell['code'] => $cell['valeur']])->sortKeys()->all());

        $this->assertSame('Véhicule test', $line18->get(1098)['valeur']);
        $this->assertSame('60', $line18->get(1100)['valeur']);
        $this->assertSame('200000.00', $line18->get(1101)['valeur']);
        $this->assertSame(14, $values->count());
        $this->assertSame(14, $values->unique(fn ($cell) => $cell['code'].':'.$cell['ligne'])->count());
        $this->assertSame([], $values->pluck('code')->diff(range(1098, 1108))->values()->all());
        $this->assertFalse($values->contains(fn ($cell) => $cell['ligne'] === null));
        $this->assertFalse($values->contains('code', 53));
    }

    public function test_fractional_contract_duration_is_a_blocking_integer_error(): void
    {
        $this->seedCreditBailCatalog();
        $user = User::factory()->create();
        $this->storeFields($user, ['r0_c3' => '36.50']);

        $service = app(EdiXmlGeneratorService::class);
        $values = $this->mappedCreditBailValues($user, $service);
        $this->assertSame('36.50', $values->firstWhere('code', 1100)['valeur']);

        $method = new ReflectionMethod($service, 'validateOfficialIntegerValues');
        $errors = $method->invoke($service, $values->all());

        $this->assertSame('EDI_INVALID_INTEGER_VALUE', $errors[0]['id']);
        $this->assertTrue($errors[0]['bloquant']);
    }

    public function test_empty_credit_bail_table_does_not_create_edi_cells(): void
    {
        $this->seedCreditBailCatalog();
        $user = User::factory()->create();

        $this->assertTrue($this->mappedCreditBailValues($user)->isEmpty());
    }

    private function seedCreditBailCatalog(): void
    {
        $types = [
            1098 => 'Texte',
            1099 => 'Date',
            1100 => 'Entier',
            1101 => 'Double',
            1102 => 'Double',
            1103 => 'Double',
            1104 => 'Double',
            1105 => 'Double',
            1106 => 'Double',
            1107 => 'Double',
            1108 => 'Texte',
        ];

        foreach ($types as $code => $type) {
            DB::table('ref_codes_edi')->insert([
                'code_edi' => (string) $code,
                'tableau' => 'TABLEAU DES BIENS EN CREDIT-BAIL',
                'col2' => $type,
            ]);
        }
    }

    private function storeFields(User $user, array $fields): void
    {
        foreach ($fields as $key => $value) {
            LiasseData::create([
                'user_id' => $user->id,
                'exercice' => 2026,
                'tableau_code' => 'credit_bail',
                'cle' => $key,
                'valeur' => $value,
            ]);
        }
    }

    private function mappedCreditBailValues(User $user, ?EdiXmlGeneratorService $service = null)
    {
        $service ??= app(EdiXmlGeneratorService::class);
        $method = new ReflectionMethod($service, 'buildMappedValues');

        return collect($method->invoke($service, $service->context($user->id, 2026)))
            ->where('tableau', 23)
            ->values();
    }
}
