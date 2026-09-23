@extends('layouts.app')
@section('title','Sumber Harga')
@section('content')
<section class="panel"><div class="section-head"><div><p class="eyebrow">Otomasi katalog</p><h2>Sumber resmi dan survei</h2></div><a class="button" href="{{ route('price-sources.create') }}">Daftarkan sumber</a></div><p>Sumber aktif menjadi antrean pengambilan data. Harga hasil ekstraksi perlu disetujui estimator sebelum dipakai BoQ.</p><table><thead><tr><th>Sumber</th><th>Wilayah</th><th>Dokumen</th><th>Tahun</th><th>Status</th></tr></thead><tbody>@forelse($sources as $source)<tr><td><a href="{{ $source->url }}" target="_blank">{{ $source->name }}</a><small>{{ $source->publisher }}</small></td><td>{{ $source->region?->name ?? 'Nasional' }}</td><td>{{ strtoupper($source->document_type) }}</td><td>{{ $source->reference_year }}</td><td><span class="pill">{{ $source->status }}</span></td></tr>@empty<tr><td colspan="5">Belum ada sumber terdaftar.</td></tr>@endforelse</tbody></table></section>
@endsection
