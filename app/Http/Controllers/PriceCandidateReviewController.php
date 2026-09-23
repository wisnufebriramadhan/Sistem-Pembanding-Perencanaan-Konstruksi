<?php

namespace App\Http\Controllers;

use App\Models\PriceCandidate;
use App\Models\ReferencePrice;
use Illuminate\Http\Request;

class PriceCandidateReviewController extends Controller
{
    public function index()
    {
        return view('price-candidates.index', [
            'candidates' => PriceCandidate::with(['source.region', 'reviewer'])->latest('received_at')->paginate(20),
        ]);
    }

    public function approve(Request $request, PriceCandidate $priceCandidate)
    {
        abort_unless($priceCandidate->status === 'pending', 422, 'Kandidat ini sudah direview.');

        $data = $request->validate([
            'category' => ['required', 'in:material,upah,alat,pekerjaan'],
            'item_code' => ['nullable', 'max:50'],
        ]);
        $source = $priceCandidate->source;

        ReferencePrice::create([
            'region_id' => $source->region_id,
            'item_code' => $data['item_code'] ?? null,
            'name' => $priceCandidate->name,
            'category' => $data['category'],
            'unit' => $priceCandidate->unit,
            'unit_price' => $priceCandidate->unit_price,
            'source_name' => $source->name,
            'source_type' => $source->type,
            'priority' => $source->type === 'local_survey' ? 10 : 20,
            'source_year' => $source->reference_year,
            'source_url' => $source->url,
            'region' => $source->region?->name,
            'is_active' => true,
            'created_by' => $request->user()->id,
        ]);
        $priceCandidate->update([
            'status' => 'approved',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        return redirect()->route('price-candidates.index')->with('success', 'Kandidat disetujui dan ditambahkan ke Bank Harga.');
    }

    public function reject(Request $request, PriceCandidate $priceCandidate)
    {
        abort_unless($priceCandidate->status === 'pending', 422, 'Kandidat ini sudah direview.');

        $data = $request->validate(['rejection_reason' => ['required', 'string', 'max:2000']]);
        $priceCandidate->update([
            'status' => 'rejected',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => $data['rejection_reason'],
        ]);

        return redirect()->route('price-candidates.index')->with('success', 'Kandidat ditolak dan jejak audit disimpan.');
    }
}
