@extends('layouts.app')
@section('title','Impor BoQ')
@section('content')
<section class="panel import"><p class="eyebrow">Excel .xlsx</p><h2>Ekstrak dan cocokkan BoQ</h2><p>Item dipisahkan dari judul dan subtotal. Harga lokal diprioritaskan.</p><form method="POST" action="{{ route('boq-imports.store') }}" enctype="multipart/form-data">@csrf<label>File BoQ<input type="file" name="boq" accept=".xlsx" required></label><label>Wilayah harga<select name="region_id"><option value="">Baseline nasional / belum dipilih</option><optgroup label="Provinsi">@foreach($regions->where('type','province') as $region)<option value="{{ $region->id }}">{{ $region->name }}</option>@endforeach</optgroup><optgroup label="Kabupaten / Kota">@foreach($regions->where('type','city') as $region)<option value="{{ $region->id }}">{{ $region->name }}</option>@endforeach</optgroup></select></label><button class="primary">Ekstrak BoQ</button></form></section>
@endsection
