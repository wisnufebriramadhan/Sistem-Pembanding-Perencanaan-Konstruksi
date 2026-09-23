<?php

namespace Tests\Feature;

use App\Models\BoqImport;
use App\Models\CrawlRequest;
use App\Models\PriceSource;
use App\Models\Project;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrawlQueueApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_worker_can_fetch_an_empty_queue_with_the_sync_token(): void
    {
        config(['services.sp2k.sync_token' => 'test-sync-token']);

        $response = $this->withToken('test-sync-token')->postJson('/api/crawls/next');

        $response->assertOk()->assertExactJson(['job' => null]);
    }

    public function test_worker_cannot_fetch_the_queue_without_the_sync_token(): void
    {
        config(['services.sp2k.sync_token' => 'test-sync-token']);

        $this->postJson('/api/crawls/next')->assertUnauthorized();
    }

    public function test_worker_receives_active_sources_ordered_by_region_scope(): void
    {
        config(['services.sp2k.sync_token' => 'test-sync-token']);
        $user = User::factory()->create();
        $province = Region::create(['name' => 'Daerah Istimewa Yogyakarta', 'type' => 'province', 'code' => '34']);
        $city = Region::create(['name' => 'Kota Yogyakarta', 'type' => 'city', 'code' => '3471', 'parent_id' => $province->id]);
        $project = Project::create(['code' => 'TEST-001', 'name' => 'Proyek Uji', 'status' => 'draft', 'created_by' => $user->id]);
        $boq = BoqImport::create(['project_id' => $project->id, 'region_id' => $city->id, 'original_filename' => 'uji.xlsx', 'stored_path' => 'imports/uji.xlsx', 'status' => 'completed', 'items' => [], 'item_count' => 0, 'matched_count' => 0, 'created_by' => $user->id]);
        CrawlRequest::create(['boq_import_id' => $boq->id, 'region_id' => $city->id, 'sources' => ['government'], 'items' => [], 'bank_hit_count' => 0, 'queued_item_count' => 0, 'created_by' => $user->id]);

        foreach ([[$city->id, 'Kota'], [$province->id, 'Provinsi'], [null, 'Nasional']] as [$regionId, $name]) {
            PriceSource::create(['region_id' => $regionId, 'name' => "JDIH {$name}", 'publisher' => 'Pemerintah', 'type' => 'government', 'document_type' => 'xlsx', 'url' => "https://example.test/{$name}", 'reference_year' => 2027, 'status' => 'active', 'created_by' => $user->id]);
        }

        PriceSource::create(['region_id' => $city->id, 'name' => 'Tidak Aktif', 'publisher' => 'Pemerintah', 'type' => 'government', 'document_type' => 'xlsx', 'url' => 'https://example.test/inactive', 'reference_year' => 2027, 'status' => 'inactive', 'created_by' => $user->id]);

        $response = $this->withToken('test-sync-token')->postJson('/api/crawls/next');

        $response->assertOk()
            ->assertJsonPath('job.status', 'processing')
            ->assertJsonPath('price_sources.0.name', 'JDIH Kota')
            ->assertJsonPath('price_sources.0.scope', 'local')
            ->assertJsonPath('price_sources.1.name', 'JDIH Provinsi')
            ->assertJsonPath('price_sources.1.scope', 'province')
            ->assertJsonPath('price_sources.2.name', 'JDIH Nasional')
            ->assertJsonPath('price_sources.2.scope', 'national')
            ->assertJsonCount(3, 'price_sources');
    }

    public function test_national_source_is_not_mislabelled_when_the_boq_region_is_a_province(): void
    {
        config(['services.sp2k.sync_token' => 'test-sync-token']);
        $user = User::factory()->create();
        $province = Region::create(['name' => 'Daerah Istimewa Yogyakarta', 'type' => 'province', 'code' => '34']);
        $project = Project::create(['code' => 'TEST-002', 'name' => 'Proyek Uji Provinsi', 'status' => 'draft', 'created_by' => $user->id]);
        $boq = BoqImport::create(['project_id' => $project->id, 'region_id' => $province->id, 'original_filename' => 'uji.xlsx', 'stored_path' => 'imports/uji.xlsx', 'status' => 'completed', 'items' => [], 'item_count' => 0, 'matched_count' => 0, 'created_by' => $user->id]);
        CrawlRequest::create(['boq_import_id' => $boq->id, 'region_id' => $province->id, 'sources' => ['government'], 'items' => [], 'bank_hit_count' => 0, 'queued_item_count' => 0, 'created_by' => $user->id]);
        PriceSource::create(['name' => 'JDIH Nasional', 'publisher' => 'Pemerintah', 'type' => 'government', 'document_type' => 'xlsx', 'url' => 'https://example.test/national', 'reference_year' => 2027, 'status' => 'active', 'created_by' => $user->id]);

        $this->withToken('test-sync-token')->postJson('/api/crawls/next')
            ->assertOk()
            ->assertJsonPath('price_sources.0.scope', 'national');
    }
}
