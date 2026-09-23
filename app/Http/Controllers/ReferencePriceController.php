<?php

namespace App\Http\Controllers;

use App\Models\ReferencePrice;
use App\Models\Region;
use Illuminate\Http\Request;

class ReferencePriceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('prices.index', ['prices' => ReferencePrice::with('region')->latest()->paginate(20)]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('prices.create', ['regions' => Region::orderBy('name')->get()]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'max:200'], 'item_code' => ['nullable', 'max:50'], 'region_id' => ['nullable', 'exists:regions,id'], 'category' => ['required', 'in:material,upah,alat,pekerjaan'], 'unit' => ['required', 'max:30'], 'unit_price' => ['required', 'numeric', 'min:0'], 'source_name' => ['required', 'max:150'], 'source_type' => ['required', 'in:government,local_survey,internal'], 'source_year' => ['required', 'integer', 'between:2020,2100'], 'source_url' => ['nullable', 'url'], 'effective_date' => ['nullable', 'date']]);
        ReferencePrice::create($data + ['priority' => $data['source_type'] === 'local_survey' ? 10 : 20, 'is_active' => true, 'created_by' => $request->user()->id]);

        return redirect()->route('reference-prices.index')->with('success', 'Harga acuan tersimpan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
