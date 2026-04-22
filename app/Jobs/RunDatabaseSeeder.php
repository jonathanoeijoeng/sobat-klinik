<?php

// app/Jobs/RunDatabaseSeeder.php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class RunDatabaseSeeder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 300; // 5 menit

    public function __construct(
        public int $jumlahData,
        public int $rentangHari,
    ) {}

    public function handle(): void
    {
        Cache::put('seeder_status', 'running', now()->addMinutes(10));

        try {
            // Simpan ke cache SEBELUM artisan call
            Cache::store('file')->put('seeder_jumlah_data', $this->jumlahData, now()->addMinutes(10));
            Cache::store('file')->put('seeder_rentang_hari', $this->rentangHari, now()->addMinutes(10));

            Artisan::call('migrate:fresh', ['--seed' => true]);

            Cache::put('seeder_status', 'done', now()->addMinutes(10));
        } catch (\Exception $e) {
            Cache::put('seeder_status', 'error:' . $e->getMessage(), now()->addMinutes(10));
        }
    }
}