@extends('layouts.app')
@section('title','Harga Acuan')
@section('content')
<section class="panel"><div class="section-head"><div><p class="eyebrow">Katalog harga</p><h2>Harga berdasarkan wilayah</h2></div><a class="button" href="{{ route('reference-prices.create') }}">Tambah harga</a></div>@if(session('success'))<p>{{ session('success') }}</p>@endif<table><thead><tr><th>Item</th><th>Wilayah</th><th>Harga</th><th>Sumber</th><th>Tahun</th></tr></thead><tbody>@forelse($prices as $price)<tr><td>{{ $price->name }}<small>{{ $price->unit }}</small></td><td>{{ $price->region?->name ?? 'Baseline nasional' }}</td><td>Rp {{ number_format($price->unit_price,0,',','.') }}</td><td>{{ $price->source_name }}</td><td>{{ $price->source_year }}</td></tr>@empty<tr><td colspan="5">Belum ada harga.</td></tr>@endforelse</tbody></table></section>
@endsection
