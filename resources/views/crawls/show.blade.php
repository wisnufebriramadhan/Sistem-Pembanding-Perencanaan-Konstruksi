@extends('layouts.app')
@section('title','Pencarian Harga')
@section('content')
<section class="panel"><p class="eyebrow">Bank Harga</p><h2>{{ $crawl->bank_hit_count }} item tersedia, {{ $crawl->queued_item_count }} item masuk antrean</h2><p>Sumber pemerintah dan marketplace dipilih otomatis berdasarkan wilayah BoQ. Hasil crawler menjadi kandidat review sebelum masuk Bank Harga.</p><table><thead><tr><th>Item yang dicari</th><th>Satuan</th><th>Status</th></tr></thead><tbody>@forelse($crawl->items as $item)<tr><td>{{ $item['description'] }}</td><td>{{ $item['unit'] }}</td><td><span class="pill">queued</span></td></tr>@empty<tr><td colspan="3">Semua item tersedia di Bank Harga.</td></tr>@endforelse</tbody></table></section>
@endsection
