<?php

namespace Tests\Feature;

use App\Models\PriceCandidate;
use App\Models\PriceSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceCandidateReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_reviewer_can_approve_a_pending_candidate_into_the_reference_price_bank(): void
    {
        $reviewer = User::factory()->create(['role' => 'reviewer']);
        $candidate = $this->pendingCandidate($reviewer);

        $response = $this->actingAs($reviewer)->post(route('price-candidates.approve', $candidate), [
            'category' => 'material',
            'item_code' => 'MAT-001',
        ]);

        $response->assertRedirect(route('price-candidates.index'));
        $this->assertDatabaseHas('price_candidates', [
            'id' => $candidate->id,
            'status' => 'approved',
            'reviewed_by' => $reviewer->id,
        ]);
        $this->assertDatabaseHas('reference_prices', [
            'name' => 'Semen Portland 50 kg',
            'item_code' => 'MAT-001',
            'category' => 'material',
            'source_name' => 'SHS DIY 2026',
        ]);
    }

    public function test_reviewer_can_reject_a_candidate_and_the_reason_is_retained(): void
    {
        $reviewer = User::factory()->create(['role' => 'reviewer']);
        $candidate = $this->pendingCandidate($reviewer);

        $response = $this->actingAs($reviewer)->post(route('price-candidates.reject', $candidate), [
            'rejection_reason' => 'Satuan pada dokumen sumber tidak dapat diverifikasi.',
        ]);

        $response->assertRedirect(route('price-candidates.index'));
        $this->assertDatabaseHas('price_candidates', [
            'id' => $candidate->id,
            'status' => 'rejected',
            'reviewed_by' => $reviewer->id,
            'rejection_reason' => 'Satuan pada dokumen sumber tidak dapat diverifikasi.',
        ]);
        $this->assertDatabaseCount('reference_prices', 0);
    }

    private function pendingCandidate(User $createdBy): PriceCandidate
    {
        $source = PriceSource::create([
            'name' => 'SHS DIY 2026',
            'publisher' => 'Pemerintah Daerah DIY',
            'type' => 'government',
            'document_type' => 'pdf',
            'url' => 'https://example.test/shs-diy-2026.pdf',
            'reference_year' => 2026,
            'status' => 'active',
            'created_by' => $createdBy->id,
        ]);

        return PriceCandidate::create([
            'price_source_id' => $source->id,
            'external_key' => 'semen-50kg',
            'name' => 'Semen Portland 50 kg',
            'unit' => 'sak',
            'unit_price' => 65000,
            'status' => 'pending',
            'received_at' => now(),
        ]);
    }
}
