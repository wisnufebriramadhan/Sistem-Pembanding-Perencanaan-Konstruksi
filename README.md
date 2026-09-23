# SP2K — Sistem Pembanding Perencanaan Konstruksi

Fondasi aplikasi Laravel untuk estimasi awal konstruksi berbasis harga acuan SSH/HSPK dan faktor koreksi yang tercatat.

## Menjalankan lokal

```bash
composer install
php artisan migrate:fresh --seed
php artisan serve
```

Buka `http://127.0.0.1:8000`. Akun pengembangan awal: `admin@sp2k.test`, kata sandi `ChangeMe!123`. Ganti atau hapus akun ini sebelum lingkungan selain lokal.

## Domain utama

- **Proyek**: konteks estimasi dan pemilik data.
- **Harga acuan**: katalog material, upah, atau alat lengkap dengan sumber, tahun, wilayah, dan masa berlaku.
- **Faktor koreksi**: K1 (waktu/inflasi), K2 (skala), K3 (logistik), atau faktor lain. Nilai disimpan sebagai multiplier agar jejak hitung jelas.
- **Matriks pembanding**: nantinya memuat item BoQ, harga acuan yang dipilih, kuantitas, faktor gabungan, dan hasil estimasi.

Rumus pada level item: `harga estimasi satuan = harga acuan satuan × multiplier koreksi`; total item = harga estimasi satuan × kuantitas.

## RBAC

Kolom `users.role` dan middleware `role` sudah tersedia. Peran yang disiapkan: `admin`, `estimator`, `reviewer`, dan `viewer`. Akses sensitif wajib diproteksi di route/policy; jangan hanya disembunyikan dari menu.

## Antrean crawler n8n

Impor workflow `n8n-sp2k-crawl-workflow.json` ke n8n dan isi environment n8n: `SP2K_URL=http://host.docker.internal:8000` serta token yang sama pada `SP2K_SYNC_TOKEN` di aplikasi dan n8n. Workflow mengambil antrean melalui `POST /api/crawls/next`, memproses item, mengirim kandidat ke `POST /api/price-candidates`, lalu menutup antrean melalui `POST /api/crawls/{id}/complete`. Respons antrean menyertakan `price_sources` aktif yang diurutkan sesuai wilayah BoQ: kabupaten/kota, provinsi, lalu nasional. Adapter crawler harus dibuat per format sumber resmi/marketplace (XLSX, CSV, PDF, atau HTML) agar spesifikasi dan ketentuan sumber tetap terjaga.

## Tahap setelah sampel BoQ tersedia

1. Petakan header dan kolom (kode, uraian, satuan, volume, harga) dari format aktual.
2. Buat import tervalidasi beserta preview dan laporan baris gagal.
3. Hubungkan item BoQ ke katalog SSH/HSPK dan faktor koreksi per proyek/item.
4. Tambahkan approval reviewer dan log audit untuk perubahan harga serta hasil estimasi.
