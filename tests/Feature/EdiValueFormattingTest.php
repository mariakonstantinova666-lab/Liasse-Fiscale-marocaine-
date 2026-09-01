<?php

namespace Tests\Feature;

use App\Services\EdiXmlGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

class EdiValueFormattingTest extends TestCase
{
    use RefreshDatabase;

    public function test_official_integer_values_are_formatted_as_exact_strings(): void
    {
        $this->catalogCode(14040, 'Entier');
        $service = app(EdiXmlGeneratorService::class);

        foreach ([
            '5' => '5',
            '5.00' => '5',
            '2000' => '2000',
            '18452399' => '18452399',
            '0018452399' => '0018452399',
            '9007199254740993' => '9007199254740993',
        ] as $input => $expected) {
            $this->assertSame($expected, $this->format($service, 14040, $input));
        }
    }

    public function test_non_zero_fraction_is_rejected_for_an_official_integer(): void
    {
        $this->catalogCode(14040, 'Entier');
        $service = app(EdiXmlGeneratorService::class);
        $formatted = $this->format($service, 14040, '5.50');

        $this->assertSame('5.50', $formatted);

        $method = new ReflectionMethod($service, 'validateOfficialIntegerValues');
        $errors = $method->invoke($service, [[
            'tableau' => 28,
            'code' => 14040,
            'valeur' => $formatted,
            'ligne' => 1,
        ]]);

        $this->assertSame('EDI_INVALID_INTEGER_VALUE', $errors[0]['id']);
        $this->assertTrue($errors[0]['bloquant']);
    }

    public function test_official_double_variants_keep_two_decimal_formatting(): void
    {
        foreach (['Dou', 'Doub', 'Doubl', 'Double'] as $index => $type) {
            $this->catalogCode(1271 + $index, $type);
        }
        $service = app(EdiXmlGeneratorService::class);

        $this->assertSame('52800.00', $this->format($service, 1271, '52800'));
        $this->assertSame('52800.50', $this->format($service, 1272, '52800.5'));
        $this->assertSame('52800.50', $this->format($service, 1273, '52 800,50'));
        $this->assertSame('-125.50', $this->format($service, 1274, '-125.5'));
    }

    public function test_official_double_percentage_suffix_is_removed_without_scaling(): void
    {
        $this->catalogCode(1082, 'Double');
        $this->catalogCode(22, 'Double');
        $service = app(EdiXmlGeneratorService::class);

        $this->assertSame('20.00', $this->format($service, 1082, '20.00%'));
        $this->assertSame('10.00', $this->format($service, 1082, '10.00%'));
        $this->assertSame('20.00', $this->format($service, 1082, '20%'));
        $this->assertSame('20.00', $this->format($service, 1082, '20,00%'));
        $this->assertSame('367993.01', $this->format($service, 22, '367993.01'));
    }

    public function test_text_and_date_values_are_not_converted(): void
    {
        $this->catalogCode(14041, 'Text');
        $this->catalogCode(14042, 'Texte');
        $this->catalogCode(1270, 'Date');
        $service = app(EdiXmlGeneratorService::class);

        $this->assertSame('AB556214', $this->format($service, 14041, 'AB556214'));
        $this->assertSame('00123', $this->format($service, 14042, '00123'));
        $this->assertSame('01/01/2024', $this->format($service, 1270, '01/01/2024'));
    }

    public function test_missing_catalog_type_uses_historical_fallback(): void
    {
        $service = app(EdiXmlGeneratorService::class);

        $this->assertSame('5.00', $this->format($service, 999999, '5'));
        $this->assertSame('00123', $this->format($service, 999999, '00123', false));
    }

    private function catalogCode(int $code, string $type): void
    {
        DB::table('ref_codes_edi')->insert([
            'code_edi' => (string) $code,
            'col2' => $type,
        ]);
    }

    private function format(EdiXmlGeneratorService $service, int $code, string $value, bool $formatNumeric = true): string
    {
        $method = new ReflectionMethod($service, 'formatValueForCode');

        return $method->invoke($service, $code, $value, $formatNumeric);
    }
}
