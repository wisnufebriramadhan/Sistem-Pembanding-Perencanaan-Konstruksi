<?php

namespace Tests\Feature;

use App\Models\BoqImport;
use App\Models\CrawlRequest;
use App\Models\PriceSource;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceSyncApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_worker_must_submit_a_candidate_before_completing_a_crawl(): void
    {
        config(['services.sp2k.sync_token' => 'test-token']);
        [$crawl, $source] = $this->processingCrawlAndSource();

        $this->withToken('test-token')->postJson("/api/crawls/{$crawl->id}/complete", ['status' => 'completed'])
            ->assertUnprocessable();

        $this->withToken('test-token')->postJson('/api/price-candidates', [
            'crawl_id' => $crawl->id,
            'source_id' => $source->id,
            'items' => [[
                'external_key' => 'semen-50kg',
                'name' => 'Semen Portland 50 kg',
                'unit' => 'sak',
                'unit_price' => 65000,
            ]],
        ])->assertOk()->assertJson(['accepted' => 1]);

        $this->withToken('test-token')->postJson("/api/crawls/{$crawl->id}/complete", ['status' => 'completed'])
            ->assertOk();

        $this->assertDatabaseHas('price_candidates', ['crawl_request_id' => $crawl->id, 'price_source_id' => $source->id]);
        $this->assertDatabaseHas('crawl_requests', ['id' => $crawl->id, 'status' => 'completed']);
    }

    private function processingCrawlAndSource(): array
    {
        $user = User::factory()->create();
        $region = Region::create(['name' => 'Daerah Istimewa Yogyakarta', 'type' => 'province', 'code' => '34']);
        $boq = BoqImport::create(['region_id' => $region->id, 'original_filename' => 'uji.xlsx', 'stored_path' => 'uji.xlsx', 'status' => 'preview', 'items' => [], 'item_count' => 0, 'matched_count' => 0, 'created_by' => $user->id]);
        $crawl = CrawlRequest::create(['boq_import_id' => $boq->id, 'region_id' => $region->id, 'sources' => ['government'], 'items' => [], 'status' => 'processing', 'bank_hit_count' => 0, 'queued_item_count' => 1, 'created_by' => $user->id]);
        $source = PriceSource::create(['region_id' => $region->id, 'name' => 'SHS DIY 2026', 'publisher' => 'Pemda DIY', 'type' => 'government', 'document_type' => 'pdf', 'url' => 'https://example.test/shs.pdf', 'reference_year' => 2026, 'status' => 'active', 'created_by' => $user->id]);

        return [$crawl, $source];
    }
}
