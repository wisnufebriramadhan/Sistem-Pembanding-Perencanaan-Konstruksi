<?php

namespace App\Http\Controllers;

use App\Models\BoqImport;
use App\Models\ReferencePrice;
use App\Models\Region;
use App\Services\Boq\XlsxBoqReader;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BoqImportController extends Controller
{
    public function create()
    {
        return view('imports.create', ['regions' => Region::orderBy('name')->get(), 'imports' => BoqImport::latest()->take(8)->get()]);
    }

    public function store(Request $request, XlsxBoqReader $reader)
    {
        $data = $request->validate(['boq' => ['required', 'file', 'mimes:xlsx', 'max:10240'], 'region_id' => ['nullable', 'exists:regions,id']]);
        $file = $request->file('boq');
        $path = $file->store('boq-imports');
        $parsed = $reader->read(storage_path('app/private/'.$path));
        $prices = ReferencePrice::where('is_active', true)->orderBy('priority')->get();
        $matched = 0;
        $items = collect($parsed['items'])->map(function ($item) use ($prices, $data, &$matched) {
            $p = $this->match($item, $prices, $data['region_id'] ?? null);
            if ($p) {
                $matched++;
                $item['match'] = ['price_id' => $p->id, 'name' => $p->name, 'unit_price' => (float) $p->unit_price, 'source' => $p->source_name, 'source_year' => $p->source_year];
            } else {
                $item['match'] = null;
            }

return $item;
        })->all();
        $import = BoqImport::create(['region_id' => $data['region_id'] ?? null, 'original_filename' => $file->getClientOriginalName(), 'stored_path' => $path, 'project_metadata' => $parsed['metadata'], 'items' => $items, 'item_count' => count($items), 'matched_count' => $matched, 'created_by' => $request->user()->id]);

        return redirect()->route('boq-imports.show', $import);
    }

    public function show(BoqImport $boqImport)
    {
        return view('imports.show', ['import' => $boqImport]);
    }

    private function match(array $item, $prices, ?int $regionId): ?ReferencePrice
    {
        $q = Str::of($item['description'])->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->trim();

        return $prices->filter(fn ($p) => $p->unit === $item['unit'] && (! $regionId || $p->region_id === $regionId || ! $p->region_id))->sortBy(fn ($p) => ($p->region_id === $regionId ? 0 : 100) + $p->priority)->first(fn ($p) => Str::contains($q, Str::of($p->name)->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->trim()));
    }
}
