<?php

namespace Database\Seeders;

use App\Models\PriceSource;
use App\Models\Project;
use App\Models\ReferencePrice;
use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(['email' => 'admin@sp2k.test'], ['name' => 'Administrator SP2K', 'password' => Hash::make('ChangeMe!123'), 'role' => 'admin']);
        $diy = Region::firstOrCreate(['code' => '34'], ['name' => 'DI Yogyakarta', 'type' => 'province']);
        $yogyakarta = Region::firstOrCreate(['code' => '3471'], ['name' => 'Kota Yogyakarta', 'type' => 'city', 'parent_id' => $diy->id]);
        PriceSource::firstOrCreate(['name' => 'SHS DIY Tahun Anggaran 2025'], ['publisher' => 'Pemerintah Daerah DIY', 'region_id' => $diy->id, 'type' => 'government', 'document_type' => 'html', 'url' => 'https://jdih.jogjaprov.go.id/produk-hukum/peraturan-perundang-undangan/detail/keputusan-gubernur-daerah-istimewa-yogyakarta-nomor-373-tahun-2025-tentang-perubahan-kesembilan-atas-keputusan-gubernur-daerah-istimewa-yogyakarta-nomor-74-kep-2024-tentang-standar-harga-satuan-barang-pemerintah-daerah-dearah-istimewa-yogyakarta-tahun-anggaran-2025_1768885933-41?tema=9', 'reference_year' => 2025, 'status' => 'active', 'created_by' => $admin->id]);
        Project::firstOrCreate(['code' => 'SP2K-001'], ['name' => 'Estimasi Rumah Tinggal', 'client' => 'Contoh Klien', 'location' => 'Jakarta', 'status' => 'draft', 'created_by' => $admin->id]);
        ReferencePrice::firstOrCreate(['name' => 'Semen Portland 50 kg', 'source_year' => 2025], ['region_id' => $yogyakarta->id, 'item_code' => 'MAT-SEM-001', 'category' => 'material', 'unit' => 'sak', 'unit_price' => 62000, 'source_name' => 'SHS DIY (contoh)', 'source_type' => 'government', 'priority' => 20, 'region' => 'Kota Yogyakarta', 'effective_date' => '2025-01-01', 'created_by' => $admin->id]);
    }
}
