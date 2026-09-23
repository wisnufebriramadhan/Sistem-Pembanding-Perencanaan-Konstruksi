<?php

namespace App\Services\PriceSources;

use App\Models\CrawlRequest;
use App\Models\PriceSource;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;

class GovernmentPdfPriceExtractor
{
    /**
     * Extract rows that are sufficiently similar to an item requested in a BoQ.
     * The raw document is never retained: the source URL is kept on PriceSource
     * and the temporary PDF/text files are removed after parsing.
     */
    public function extract(CrawlRequest $crawl, PriceSource $source): array
    {
        $response = Http::timeout(60)->accept('application/pdf')->get($source->url);
        $response->throw();

        $document = $response->body();
        if (strlen($document) > 25 * 1024 * 1024 || ! str_starts_with($document, '%PDF')) {
            return [];
        }

        $pdfPath = tempnam(sys_get_temp_dir(), 'sp2k-pdf-');
        $textPath = tempnam(sys_get_temp_dir(), 'sp2k-text-');

        try {
            file_put_contents($pdfPath, $document);
            $result = Process::timeout(90)->run(['pdftotext', '-layout', $pdfPath, $textPath]);

            if (! $result->successful() || ! file_exists($textPath)) {
                return [];
            }

            return $this->parse((string) file_get_contents($textPath), $crawl->items ?? []);
        } finally {
            @unlink($pdfPath);
            @unlink($textPath);
        }
    }

    public function parse(string $text, array $boqItems): array
    {
        $requests = collect($boqItems)
            ->map(fn (array $item) => [
                'name' => (string) ($item['description'] ?? $item['name'] ?? ''),
                'unit' => (string) ($item['unit'] ?? ''),
            ])
            ->filter(fn (array $item) => $item['name'] !== '')
            ->values();

        if ($requests->isEmpty()) {
            return [];
        }

        $items = [];
        foreach (preg_split('/\R/u', $text) as $line) {
            $row = $this->parseRow($line);
            if (! $row) {
                continue;
            }

            $match = $requests
                ->map(fn (array $request) => [...$request, 'score' => $this->similarity($request['name'], $row['name'])])
                ->sortByDesc('score')
                ->first();

            if (($match['score'] ?? 0) < 0.5 || ! $this->sameUnit($match['unit'], $row['unit'])) {
                continue;
            }

            $items[] = [
                'name' => $row['name'],
                'unit' => $row['unit'],
                'unit_price' => $row['unit_price'],
                'external_key' => sha1($row['name'].'|'.$row['unit'].'|'.$row['unit_price']),
                'matched_boq_item' => $match['name'],
                'match_score' => round($match['score'], 2),
            ];
        }

        return collect($items)->unique('external_key')->values()->all();
    }

    private function parseRow(string $line): ?array
    {
        $line = trim(preg_replace('/\s+/u', ' ', $line));
        if ($line === '') {
            return null;
        }

        if (! preg_match('/^(?:\d+[.)]\s*)?(?<name>.+?)\s+(?<unit>m²|m2|m³|m3|kg|unit|buah|bh|set|paket|sak|lembar|batang|roll|liter|ltr|jam|hari)\s+(?<price>\d{1,3}(?:[.,]\d{3})+(?:,\d{2})?|\d{5,})$/iu', $line, $match)) {
            return null;
        }

        // Government price tables use Indonesian thousands separators.
        $price = (float) preg_replace('/[^0-9]/', '', $match['price']);

        if ($price <= 0) {
            return null;
        }

        return [
            'name' => trim($match['name']),
            'unit' => $this->canonicalUnit($match['unit']),
            'unit_price' => $price,
        ];
    }

    private function similarity(string $left, string $right): float
    {
        $tokens = fn (string $value) => collect(preg_split('/[^\pL\pN]+/u', mb_strtolower($value)))
            ->filter(fn (string $token) => mb_strlen($token) >= 3 && ! in_array($token, ['dan', 'atau', 'untuk', 'dengan', 'pada', 'yang', 'harga', 'satuan']))
            ->unique()
            ->values();

        $leftTokens = $tokens($left);
        $rightTokens = $tokens($right);

        if ($leftTokens->isEmpty() || $rightTokens->isEmpty()) {
            return 0;
        }

        return $leftTokens->intersect($rightTokens)->count() / $leftTokens->count();
    }

    private function sameUnit(string $left, string $right): bool
    {
        return blank($left) || $this->canonicalUnit($left) === $this->canonicalUnit($right);
    }

    private function canonicalUnit(string $unit): string
    {
        return match (mb_strtolower(trim($unit))) {
            'm²', 'm2' => 'm²',
            'm³', 'm3' => 'm³',
            'bh' => 'buah',
            'ltr' => 'liter',
            default => mb_strtolower(trim($unit)),
        };
    }
}
