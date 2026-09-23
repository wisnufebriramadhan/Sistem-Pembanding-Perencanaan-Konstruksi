<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
        $data = $request->validate(['source_id' => ['required', 'exists:price_sources,id'], 'items' => ['required', 'array', 'max:3000'], 'items.*.name' => ['required', 'max:200'], 'items.*.unit' => ['required', 'max:30'], 'items.*.unit_price' => ['required', 'numeric', 'min:0'], 'items.*.external_key' => ['nullable', 'max:100']]);
        $source = PriceSource::findOrFail($data['source_id']);
        foreach ($data['items'] as $item) {
            PriceCandidate::updateOrCreate(['price_source_id' => $source->id, 'external_key' => $item['external_key'] ?? $item['name']], ['name' => $item['name'], 'unit' => $item['unit'], 'unit_price' => $item['unit_price'], 'status' => 'pending', 'received_at' => now(), 'raw_payload' => $item]);
        } $source->update(['last_checked_at' => now()]);

        return response()->json(['accepted' => count($data['items'])]);
    }
}
