<?php

namespace Tests\Feature;

use App\Models\BoqImport;
use App\Models\CrawlRequest;
use App\Models\PriceSource;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CrawlRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_estimator_can_queue_an_automatic_price_search_without_selecting_sources(): void
    {
        Http::fake([
            'https://jdihn.go.id/search*' => Http::response(
                '<script>{"id":42,"title":"Standar Harga Satuan Kota Yogyakarta","instansi":"JDIH Pemerintah Kota Yogyakarta","status":"Tidak Berlaku","tahunTerbit":2009}{"id":43,"title":"Standar Harga Satuan Kota Yogyakarta Tahun 2027","instansi":"JDIH Pemerintah Kota Yogyakarta","status":"Berlaku","tahunTerbit":2026}</script>'
            ),
        ]);

        $estimator = User::factory()->create(['role' => 'estimator']);
        $region = Region::create(['name' => 'Kota Yogyakarta', 'type' => 'city', 'code' => '3471']);
        $boq = BoqImport::create([
            'region_id' => $region->id,
            'original_filename' => 'uji.xlsx',
            'stored_path' => 'boq-imports/uji.xlsx',
            'status' => 'preview',
            'items' => [['description' => 'Pipa PVC', 'unit' => 'm', 'quantity' => 10]],
            'item_count' => 1,
            'matched_count' => 0,
            'created_by' => $estimator->id,
        ]);

        $this->actingAs($estimator)
            ->post(route('crawls.store', $boq))
            ->assertRedirect();

        $crawl = CrawlRequest::sole();

        $this->assertSame(['government', 'marketplace'], $crawl->sources);
        $this->assertSame($region->id, $crawl->region_id);
        $this->assertSame(1, $crawl->queued_item_count);
        $this->assertSame('Standar Harga Satuan Kota Yogyakarta Tahun 2027', PriceSource::sole()->name);
        $this->assertSame('https://jdihn.go.id/api/doc/43/file?action=download', PriceSource::sole()->url);
    }

    public function test_queue_api_adds_a_current_national_source_when_no_current_regional_document_is_found(): void
    {
        Http::fake(['https://jdihn.go.id/search*' => Http::response('<html></html>')]);
        config(['services.sp2k.sync_token' => 'test-token']);
        $user = User::factory()->create();
        $region = Region::create(['name' => 'Daerah Istimewa Yogyakarta', 'type' => 'province', 'code' => '34']);
        $boq = BoqImport::create(['region_id' => $region->id, 'original_filename' => 'uji.xlsx', 'stored_path' => 'uji.xlsx', 'status' => 'preview', 'items' => [], 'item_count' => 0, 'matched_count' => 0, 'created_by' => $user->id]);
        CrawlRequest::create(['boq_import_id' => $boq->id, 'region_id' => $region->id, 'sources' => ['government'], 'items' => [], 'status' => 'queued', 'bank_hit_count' => 0, 'queued_item_count' => 1, 'created_by' => $user->id]);

        $this->withToken('test-token')->postJson('/api/crawls/next')
            ->assertOk()
            ->assertJsonPath('price_sources.0.name', 'PMK 32 Tahun 2025 - Standar Biaya Masukan Tahun Anggaran 2026')
            ->assertJsonPath('price_sources.0.scope', 'national');
    }
}
