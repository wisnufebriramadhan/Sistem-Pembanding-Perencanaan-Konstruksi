<?php

namespace App\Services\Boq;

use RuntimeException;
use ZipArchive;

class XlsxBoqReader
{
    public function read(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('File Excel tidak dapat dibuka.');
        }
        $strings = $this->sharedStrings($zip);
        $workbook = simplexml_load_string($zip->getFromName('xl/workbook.xml'));
        $workbook->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $items = [];
        $metadata = [];
        foreach ($workbook->xpath('//m:sheets/m:sheet') as $index => $sheet) {
            $xml = $zip->getFromName('xl/worksheets/sheet'.($index + 1).'.xml');
            if (! $xml) {
                continue;
            }
            $rows = $this->rows($xml, $strings);
            $metadata += $this->metadata($rows);
            foreach ($rows as $row) {
                $number = $row['A'] ?? null;
                $code = trim((string) ($row['B'] ?? ''));
                $description = trim((string) ($row['D'] ?? ''));
                $quantity = $row['F'] ?? null;
                $unit = trim((string) ($row['G'] ?? ''));
                if (! is_numeric($number) || ! $code || ! $description || ! is_numeric($quantity) || ! $unit) {
                    continue;
                }
                $items[] = ['sheet' => (string) $sheet['name'], 'code' => $code, 'description' => $description, 'quantity' => (float) $quantity, 'unit' => $unit];
            }
        }
        $zip->close();

        return compact('metadata', 'items');
    }

    private function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if (! $xml) {
            return [];
        } $s = simplexml_load_string($xml);
        $s->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        return array_map(fn ($x) => trim(strip_tags($x->asXML())), $s->xpath('//m:si'));
    }

    private function rows(string $xml, array $strings): array
    {
        $s = simplexml_load_string($xml);
        $s->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $out = [];
        foreach ($s->xpath('//m:sheetData/m:row') as $r) {
            $row = [];
            foreach ($r->c as $c) {
                preg_match('/([A-Z]+)/', (string) $c['r'], $m);
                $v = (string) $c->v;
                $row[$m[1]] = (string) $c['t'] === 's' ? ($strings[(int) $v] ?? '') : $v;
            } $out[] = $row;
        }

return $out;
    }

    private function metadata(array $rows): array
    {
        $m = [];
        foreach ($rows as $r) {
            $k = strtoupper(trim((string) ($r['B'] ?? '')));
            if (in_array($k, ['LOKASI', 'NOMOR', 'PERIHAL', 'TERBIT'], true)) {
                $m[strtolower($k)] = trim((string) ($r['E'] ?? ''));
            }
        }

return $m;
    }
}
