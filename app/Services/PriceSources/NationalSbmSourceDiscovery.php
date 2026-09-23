<?php

namespace App\Services\PriceSources;

use App\Models\CrawlRequest;
use App\Models\PriceSource;

class NationalSbmSourceDiscovery
{
    /**
     * Official nationwide fallback. This is intentionally automatic and is
     * used only when a current regional government source cannot be found.
     */
    public function discover(CrawlRequest $crawl): PriceSource
    {
        return PriceSource::updateOrCreate(
            ['url' => 'https://jdih.kemenkeu.go.id/api/download/ccce2e9f-11fe-41ee-9da3-c7d42609b484/2025pmkeuangan032.pdf'],
            [
                'region_id' => null,
                'name' => 'PMK 32 Tahun 2025 - Standar Biaya Masukan Tahun Anggaran 2026',
                'publisher' => 'Kementerian Keuangan Republik Indonesia',
                'type' => 'government',
                'document_type' => 'pdf',
                'reference_year' => 2026,
                'status' => 'active',
                'notes' => 'Fallback nasional otomatis dari JDIH Kementerian Keuangan.',
                'created_by' => $crawl->created_by,
            ]
        );
    }
}
