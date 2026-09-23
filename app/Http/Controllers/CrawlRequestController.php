<?php

namespace App\Http\Controllers;

use App\Models\BoqImport;
use App\Models\CrawlRequest;
use App\Models\ReferencePrice;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CrawlRequestController extends Controller
{
    public function store(Request $request, BoqImport $boqImport)
    {
        $data = $request->validate(['region_id' => ['nullable', 'integer', 'exists:regions,id']]);
        $prices = ReferencePrice::where('is_active', true)->get();
        $hits = 0;
        $items = collect($boqImport->items)->map(function ($item) use ($prices, &$hits) {
            $needle = Str::of($item['description'])->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->trim();
            $price = $prices->first(fn ($p) => $p->unit === $item['unit'] && Str::contains($needle, Str::of($p->name)->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->trim()));
            $item['bank_status'] = $price ? 'available' : 'crawl_needed';
            if ($price) {
                $hits++;
                $item['bank_price_id'] = $price->id;
            }

            return $item;
        })->all();
        $boqImport->update(['items' => $items, 'matched_count' => $hits]);
        $crawl = CrawlRequest::create(['boq_import_id' => $boqImport->id, 'region_id' => $data['region_id'] ?? $boqImport->region_id, 'sources' => ['government', 'marketplace'], 'items' => array_values(array_filter($items, fn ($item) => $item['bank_status'] === 'crawl_needed')), 'bank_hit_count' => $hits, 'queued_item_count' => count($items) - $hits, 'created_by' => $request->user()->id]);

        return redirect()->route('crawls.show', $crawl);
    }

    public function show(CrawlRequest $crawl)
    {
        return view('crawls.show', compact('crawl'));
    }
}
