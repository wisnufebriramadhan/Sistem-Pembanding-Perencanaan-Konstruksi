<?php

namespace App\Http\Controllers;

use App\Models\PriceSource;
use App\Models\Region;
use Illuminate\Http\Request;

class PriceSourceController extends Controller
{
    public function index()
    {
        return view('sources.index', ['sources' => PriceSource::with('region')->latest()->get()]);
    }

    public function create()
    {
        return view('sources.create', ['regions' => Region::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'max:150'], 'publisher' => ['required', 'max:150'], 'region_id' => ['nullable', 'exists:regions,id'], 'type' => ['required', 'in:government,marketplace,local_survey,internal'], 'document_type' => ['required', 'in:xlsx,pdf,csv,html'], 'url' => ['required', 'url', 'max:2048'], 'reference_year' => ['required', 'integer', 'between:2020,2100'], 'notes' => ['nullable', 'max:2000']]);
        PriceSource::create($data + ['status' => 'active', 'created_by' => $request->user()->id]);

        return redirect()->route('price-sources.index')->with('success', 'Sumber harga didaftarkan.');
    }
}
