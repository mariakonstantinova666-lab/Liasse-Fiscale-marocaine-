<?php

namespace Tests\Feature;

use App\Services\EdiXmlGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

class EdiExtraCellsTest extends TestCase
{
    use RefreshDatabase;

    public function test_applicable_t16_adds_the_four_official_extra_cells(): void
    {
        $this->seedExtraCatalog();
        $fields = [
            'r0_c3' => '4106',
            'r1_c3' => '28250',
            'r2_c3' => '14860',
            'r3_c3' => '235875',
            'r4_c3' => '67024.66',
            'r5_c3' => '8287.35',
            'r6_c3' => '5000',
            'r7_c3' => '4590',
        ];

        $values = $this->appendExtras([], ['dotations_amortissements' => $fields]);
        $t16 = collect($values)->where('tableau', 12)->keyBy('code');

        $this->assertSame('367993.01', $t16->get(22)['valeur']);
        $this->assertSame('31/12/2026', $t16->get(50)['valeur']);
        $this->assertSame('01/01/2026', $t16->get(51)['valeur']);
        $this->assertSame('31/12/2026', $t16->get(52)['valeur']);
        $this->assertCount(4, $t16);
        $this->assertTrue($t16->every(fn ($cell) => $cell['ligne'] === null));

        $xml = $this->buildXml($values);
        foreach ([22, 50, 51, 52] as $code) {
            $this->assertSame(1, substr_count($xml, '<codeEdi>'.$code.'</codeEdi>'));
        }
        $this->assertStringNotContainsString('<ligne>', $xml);
    }

    public function test_applicable_t16_keeps_zero_sum_and_absent_t16_adds_nothing(): void
    {
        $this->seedExtraCatalog();

        $applicable = $this->appendExtras([], ['dotations_amortissements' => [
            'r0_c1' => 'Immobilisation test',
            'r0_c3' => '0',
        ]]);
        $t16 = collect($applicable)->where('tableau', 12)->keyBy('code');

        $this->assertSame('0.00', $t16->get(22)['valeur']);
        $this->assertSame([22, 50, 51, 52], $t16->keys()->sort()->values()->all());

        $absent = $this->appendExtras([], ['dotations_amortissements' => [
            'total_c3' => '100',
            'montant_global' => '100',
            'r0_c3' => '0',
            'r0_c6' => '0.00%',
        ]]);
        $this->assertEmpty(collect($absent)->where('tableau', 12));
    }

    public function test_t06_extra_depends_on_significant_calculated_values(): void
    {
        $this->seedExtraCatalog();

        $applicable = $this->appendExtras([[
            'tableau' => 34,
            'code' => 1500,
            'valeur' => '1.00',
            'ligne' => null,
        ]], []);
        $code70 = collect($applicable)->where('tableau', 34)->firstWhere('code', 70);

        $this->assertSame('31/12/2026', $code70['valeur']);
        $this->assertNull($code70['ligne']);

        $zero = $this->appendExtras([[
            'tableau' => 34,
            'code' => 1500,
            'valeur' => '0.00',
            'ligne' => null,
        ]], []);
        $this->assertFalse(collect($zero)->contains('code', 70));
    }

    public function test_unconfigured_extras_remain_absent_and_code_18_is_not_duplicated(): void
    {
        $this->seedExtraCatalog();
        $values = $this->appendExtras([[
            'tableau' => 41,
            'code' => 18,
            'valeur' => '200000.00',
            'ligne' => null,
        ]], []);
        $collection = collect($values);

        $this->assertSame(1, $collection->where('tableau', 41)->where('code', 18)->count());
        foreach ([53, 54, 55, 71, 72] as $code) {
            $this->assertFalse($collection->contains('code', $code));
        }
    }

    private function seedExtraCatalog(): void
    {
        foreach ([22 => 'Double', 50 => 'Date', 51 => 'Date', 52 => 'Date', 70 => 'Date'] as $code => $type) {
            DB::table('ref_codes_edi')->insert([
                'code_edi' => (string) $code,
                'col2' => $type,
            ]);
        }
    }

    private function appendExtras(array $values, array $data): array
    {
        $service = app(EdiXmlGeneratorService::class);
        $append = new ReflectionMethod($service, 'appendConfiguredExtraValues');
        $arguments = [&$values, $this->context(), $data];
        $append->invokeArgs($service, $arguments);

        $format = new ReflectionMethod($service, 'formatMappedValuesByOfficialType');

        return $format->invoke($service, $values);
    }

    private function buildXml(array $values): string
    {
        $service = app(EdiXmlGeneratorService::class);
        $method = new ReflectionMethod($service, 'buildXml');

        return $method->invoke($service, $this->context(), $values);
    }

    private function context(): array
    {
        return [
            'societe' => null,
            'exercice' => 2026,
            'period_start' => '01/01/2026',
            'period_end' => '31/12/2026',
            'items' => collect(),
            'liasseData' => collect(),
        ];
    }
}
