<?php

namespace App\Console\Commands;

use App\Models\Region;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

#[Signature('app:sync-indonesian-regions')]
#[Description('Unduh master provinsi dan kabupaten/kota Indonesia')]
class SyncIndonesianRegions extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $base = 'https://wilayah.id/api';
        $provinces = Http::timeout(30)->get("$base/provinces.json")->throw()->json('data', []);
        foreach ($provinces as $province) {
            $p = Region::updateOrCreate(['code' => (string) $province['code']], ['name' => $province['name'], 'type' => 'province', 'parent_id' => null]);
            $cities = Http::timeout(30)->get("$base/regencies/{$province['code']}.json")->throw()->json('data', []);
            foreach ($cities as $city) {
                Region::updateOrCreate(['code' => (string) $city['code']], ['name' => $city['name'], 'type' => 'city', 'parent_id' => $p->id]);
            }
        }
        $this->info('Master wilayah diperbarui.');

        return self::SUCCESS;
    }
}
