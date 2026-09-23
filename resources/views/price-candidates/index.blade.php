@extends('layouts.app')
@section('title', 'Review Kandidat Harga')
@section('content')
<section class="panel">
    <div class="section-head">
        <div>
            <p class="eyebrow">Tahap 4 dari alur estimasi</p>
            <h2>Validasi hasil pencarian harga</h2>
        </div>
    </div>
    <p>Hanya kandidat yang disetujui masuk ke Bank Harga. Penolakan tetap dicatat sebagai jejak audit.</p>
    @if (session('success'))
        <p class="notice">{{ session('success') }}</p>
    @endif
    <table>
        <thead><tr><th>Item & sumber</th><th>Harga kandidat</th><th>Status / audit</th><th>Keputusan</th></tr></thead>
        <tbody>
            @forelse ($candidates as $candidate)
                <tr>
                    <td>
                        <strong>{{ $candidate->name }}</strong><br>
                        <small>{{ $candidate->unit }} · {{ $candidate->source->name }} · {{ $candidate->source->region?->name ?? 'Nasional' }}</small>
                    </td>
                    <td>Rp{{ number_format($candidate->unit_price, 2, ',', '.') }}</td>
                    <td>
                        <span class="pill">{{ $candidate->status }}</span>
                        @if ($candidate->reviewed_at)
                            <small>{{ $candidate->reviewer?->name }} · {{ $candidate->reviewed_at->translatedFormat('d M Y H:i') }}</small>
                        @endif
                        @if ($candidate->rejection_reason)
                            <small>Alasan: {{ $candidate->rejection_reason }}</small>
                        @endif
                    </td>
                    <td>
                        @if ($candidate->status === 'pending')
                            <form method="POST" action="{{ route('price-candidates.approve', $candidate) }}">
                                @csrf
                                <label>Kategori<select name="category" required><option value="material">Material</option><option value="upah">Upah</option><option value="alat">Alat</option><option value="pekerjaan">Pekerjaan</option></select></label>
                                <label>Kode (opsional)<input name="item_code" maxlength="50"></label>
                                <button class="primary">Setujui</button>
                            </form>
                            <form method="POST" action="{{ route('price-candidates.reject', $candidate) }}">
                                @csrf
                                <label>Alasan penolakan<textarea name="rejection_reason" required maxlength="2000"></textarea></label>
                                <button>Tolak</button>
                            </form>
                        @else
                            <small>Keputusan sudah final.</small>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">Belum ada kandidat dari crawler untuk direview.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $candidates->links() }}
</section>
@endsection
