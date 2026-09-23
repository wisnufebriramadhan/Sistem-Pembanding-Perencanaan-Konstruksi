<?php

namespace Tests\Unit;

use App\Services\PriceSources\GovernmentPdfPriceExtractor;
use Tests\TestCase;

class GovernmentPdfPriceExtractorTest extends TestCase
{
    public function test_it_extracts_only_rows_that_match_a_boq_item_and_unit(): void
    {
        $text = <<<'TEXT'
1. Pemasangan Tulangan D10 kg 21.500
2. Pengecoran Beton Fc'17 MPa m3 1.250.000
3. AC Split 1 PK unit 5.900.000
TEXT;

        $items = app(GovernmentPdfPriceExtractor::class)->parse($text, [
            ['description' => 'Pemasangan tulangan D10', 'unit' => 'kg'],
            ['description' => "Pengecoran Beton Fc'17 MPa", 'unit' => 'm³'],
        ]);

        $this->assertCount(2, $items);
        $this->assertSame('Pemasangan Tulangan D10', $items[0]['name']);
        $this->assertSame(21500.0, $items[0]['unit_price']);
        $this->assertSame('m³', $items[1]['unit']);
        $this->assertSame(1250000.0, $items[1]['unit_price']);
    }
}
