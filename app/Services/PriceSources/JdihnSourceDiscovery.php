<?php

namespace App\Services\PriceSources;

use App\Models\CrawlRequest;
use App\Models\PriceSource;
use App\Models\Region;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class JdihnSourceDiscovery
{
    private const SEARCH_URL = 'https://jdihn.go.id/search';

    public function discover(CrawlRequest $crawl): ?PriceSource
    {
        $region = $crawl->region()->with('parent')->first();

        if (! $region) {
            return null;
        }

        try {
            $documents = $this->search($region);
            $document = $this->bestMatch($documents, $region);

            if (! $document) {
                return null;
            }

            return PriceSource::updateOrCreate(
                ['url' => $this->downloadUrl($document['id'])],
                [
                    'region_id' => $region->id,
                    'name' => $document['title'],
                    'publisher' => $document['instansi'],
                    'type' => 'government',
                    'document_type' => 'pdf',
                    'reference_year' => $document['year'],
                    'status' => 'active',
                    'last_checked_at' => now(),
                    'notes' => 'Ditemukan otomatis melalui JDIHN.',
                    'created_by' => $crawl->created_by,
                ]
            );
        } catch (\Throwable $exception) {
            Log::warning('JDIHN source discovery failed.', [
                'crawl_id' => $crawl->id,
                'region_id' => $region->id,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return array<int, array{id: int, title: string, instansi: string, status: string, year: int}>
     */
    private function search(Region $region): array
    {
        $response = Http::accept('text/html')
            ->timeout(15)
            ->get(self::SEARCH_URL, [
                'q' => "{$region->name} standar harga satuan",
            ]);

        $response->throw();

        return $this->parseDocuments($response->body());
    }

    /**
     * @return array<int, array{id: int, title: string, instansi: string, status: string, year: int}>
     */
    private function parseDocuments(string $html): array
    {
        $payload = stripcslashes($html);
        preg_match_all(
            '/\{"id":(?<id>\d+),"title":"(?<title>(?:\\\\.|[^"])*)".*?"instansi":"(?<instansi>(?:\\\\.|[^"])*)".*?"status":"(?<status>(?:\\\\.|[^"])*)".*?"tahunTerbit":(?<year>\d+)/s',
            $payload,
            $matches,
            PREG_SET_ORDER
        );

        return collect($matches)
            ->map(fn (array $document) => [
                'id' => (int) $document['id'],
                'title' => Str::of($document['title'])->replace('\\"', '"')->toString(),
                'instansi' => Str::of($document['instansi'])->replace('\\"', '"')->toString(),
                'status' => Str::of($document['status'])->replace('\\"', '"')->toString(),
                'year' => (int) $document['year'],
            ])
            ->filter(fn (array $document) => Str::contains(Str::lower($document['title']), ['standar harga', 'harga satuan']))
            ->unique('id')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{id: int, title: string, instansi: string, status: string, year: int}>  $documents
     * @return array{id: int, title: string, instansi: string, status: string, year: int}|null
     */
    private function bestMatch(array $documents, Region $region): ?array
    {
        $terms = collect([$region->name, $region->parent?->name])
            ->filter()
            ->map(fn (string $name) => Str::of($name)->lower()->replace(['kabupaten ', 'kota ', 'provinsi ', 'daerah istimewa '], '')->trim()->toString())
            ->filter(fn (string $name) => Str::length($name) >= 4)
            ->all();

        return collect($documents)
            ->filter(fn (array $document) => Str::lower($document['status']) === 'berlaku')
            ->filter(fn (array $document) => $document['year'] >= now()->subYears(2)->year)
            ->map(function (array $document) use ($terms): array {
                $haystack = Str::lower("{$document['title']} {$document['instansi']}");
                $score = collect($terms)->sum(fn (string $term) => Str::contains($haystack, $term) ? 100 : 0);
                $score += Str::lower($document['status']) === 'berlaku' ? 20 : 0;
                $score += min($document['year'], now()->year + 1);
                $document['score'] = $score;

                return $document;
            })
            ->sortByDesc('score')
            ->first();
    }

    private function downloadUrl(int $documentId): string
    {
        return "https://jdihn.go.id/api/doc/{$documentId}/file?action=download";
    }
}
