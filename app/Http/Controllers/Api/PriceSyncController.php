<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CrawlRequest;
use App\Models\PriceCandidate;
use App\Models\PriceSource;
use Illuminate\Http\Request;

class PriceSyncController extends Controller
{
    public function store(Request $request)
    {
        $token = (string) config('services.sp2k.sync_token');
        abort_if(blank($token), 503, 'SP2K_SYNC_TOKEN belum diatur.');
        abort_unless(hash_equals($token, (string) $request->bearerToken()), 401);
        $data = $request->validate(['crawl_id' => ['required', 'exists:crawl_requests,id'], 'source_id' => ['required', 'exists:price_sources,id'], 'items' => ['required', 'array', 'min:1', 'max:3000'], 'items.*.name' => ['required', 'max:200'], 'items.*.unit' => ['required', 'max:30'], 'items.*.unit_price' => ['required', 'numeric', 'min:0'], 'items.*.external_key' => ['nullable', 'max:100']]);
        $crawl = CrawlRequest::with('region.parent')->findOrFail($data['crawl_id']);
        abort_unless($crawl->status === 'processing', 409, 'Antrean tidak sedang diproses.');
        $source = PriceSource::findOrFail($data['source_id']);
        $allowedRegionIds = collect([$crawl->region_id, $crawl->region?->parent_id])->filter();
        abort_unless(in_array($source->type, $crawl->sources, true) && ($source->region_id === null || $allowedRegionIds->contains($source->region_id)), 422, 'Sumber harga tidak sesuai dengan antrean.');
        foreach ($data['items'] as $item) {
            PriceCandidate::updateOrCreate(['crawl_request_id' => $crawl->id, 'price_source_id' => $source->id, 'external_key' => $item['external_key'] ?? $item['name']], ['name' => $item['name'], 'unit' => $item['unit'], 'unit_price' => $item['unit_price'], 'status' => 'pending', 'received_at' => now(), 'raw_payload' => $item]);
        } $source->update(['last_checked_at' => now()]);

        return response()->json(['accepted' => count($data['items'])]);
    }
}
