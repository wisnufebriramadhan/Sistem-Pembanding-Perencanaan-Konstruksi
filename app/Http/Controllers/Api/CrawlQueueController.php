<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CrawlRequest;
use App\Models\PriceSource;
use App\Services\PriceSources\JdihnSourceDiscovery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CrawlQueueController extends Controller
{
    public function next(Request $request, JdihnSourceDiscovery $sourceDiscovery)
    {
        $this->authorizeWorker($request);
        $crawl = DB::transaction(function () {
            $job = CrawlRequest::where('status', 'queued')->oldest()->lockForUpdate()->first();
            if ($job) {
                $job->update(['status' => 'processing']);
            }

            return $job;
        });

        if (! $crawl) {
            return response()->json(['job' => null]);
        }

        $crawl->load('region.parent');

        $priceSources = $this->priceSourcesFor($crawl);

        if (in_array('government', $crawl->sources, true) && collect($priceSources)->where('type', 'government')->isEmpty()) {
            $sourceDiscovery->discover($crawl);
            $priceSources = $this->priceSourcesFor($crawl);
        }

        return response()->json([
            'job' => $crawl,
            'price_sources' => $priceSources,
        ]);
    }

    public function complete(Request $request, CrawlRequest $crawl)
    {
        $this->authorizeWorker($request);
        abort_unless($crawl->status === 'processing', 409);
        $data = $request->validate(['status' => ['required', 'in:completed,failed']]);
        $crawl->update(['status' => $data['status'], 'completed_at' => now()]);

        return response()->json(['ok' => true]);
    }

    private function authorizeWorker(Request $request): void
    {
        $token = (string) config('services.sp2k.sync_token');
        abort_if(blank($token), 503, 'SP2K_SYNC_TOKEN belum diatur.');
        abort_unless(hash_equals($token, (string) $request->bearerToken()), 401);
    }

    private function priceSourcesFor(CrawlRequest $crawl): array
    {
        $regionId = $crawl->region_id;
        $provinceId = $crawl->region?->parent_id;

        return PriceSource::query()
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('notes')
                    ->orWhere('notes', '!=', 'Ditemukan otomatis melalui JDIHN.')
                    ->orWhere('reference_year', '>=', now()->subYears(2)->year);
            })
            ->whereIn('type', $crawl->sources)
            ->where(function ($query) use ($regionId, $provinceId) {
                $query->whereNull('region_id');

                if ($regionId) {
                    $query->orWhere('region_id', $regionId);
                }

                if ($provinceId && $provinceId !== $regionId) {
                    $query->orWhere('region_id', $provinceId);
                }
            })
            ->orderByRaw(
                'case when region_id = ? then 0 when region_id = ? then 1 when region_id is null then 2 else 3 end',
                [$regionId, $provinceId]
            )
            ->orderByDesc('reference_year')
            ->get()
            ->map(function (PriceSource $source) use ($regionId, $provinceId): array {
                $scope = $source->region_id === $regionId
                    ? 'local'
                    : ($provinceId && $source->region_id === $provinceId ? 'province' : 'national');

                return [
                    'id' => $source->id,
                    'name' => $source->name,
                    'publisher' => $source->publisher,
                    'type' => $source->type,
                    'document_type' => $source->document_type,
                    'url' => $source->url,
                    'reference_year' => $source->reference_year,
                    'scope' => $scope,
                ];
            })
            ->all();
    }
}
