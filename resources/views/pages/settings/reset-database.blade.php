<?php

namespace App\Livewire;

use App\Jobs\RunDatabaseSeeder;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Poll;
use Livewire\Component;

new class extends Component {
    public int $jumlahData = 1267;
    public int $rentangHari = 53;
    public string $status = 'idle'; // idle | running | done | error
    public string $message = '';

    protected $rules = [
        'jumlahData' => 'required|integer|min:1|max:10000',
        'rentangHari' => 'required|integer|min:1|max:365',
    ];

    public function runSeed()
    {
        if (app()->environment('production')) {
            $this->message = 'Tidak bisa dijalankan di production!';
            return;
        }

        $this->validate();

        $statusFile = storage_path('app/seeder_status.txt');
        file_put_contents($statusFile, 'running');
        $this->status = 'running';

        RunDatabaseSeeder::dispatch($this->jumlahData, $this->rentangHari);
    }

    public function checkStatus()
    {
        if ($this->status !== 'running') {
            return;
        }

        $statusFile = storage_path('app/seeder_status.txt');

        if (!file_exists($statusFile)) {
            return;
        }

        $cacheStatus = trim(file_get_contents($statusFile));

        $this->message = 'Status: ' . $cacheStatus . ' | ' . now()->format('H:i:s');

        if (str_starts_with($cacheStatus, 'error:')) {
            $this->status = 'error';
            $this->message = str_replace('error:', '', $cacheStatus);
        } elseif ($cacheStatus === 'done') {
            $this->status = 'done';
            $this->message = "Berhasil! {$this->jumlahData} data dibuat.";
        }
    }

    public function render()
    {
        return $this->view();
    }
};
?>

<div>
    <div class="p-6 max-w-md mx-auto bg-white rounded-xl shadow space-y-4">
        <h2 class="text-xl font-bold text-gray-800">🛠️ Dev Seeder</h2>

        @if ($status === 'idle' || $status === 'error')

            <div>
                <label class="block text-sm font-medium text-gray-700">Jumlah Data</label>
                <input type="number" wire:model="jumlahData" class="mt-1 w-full border rounded-lg px-3 py-2" min="1"
                    max="10000" />
                @error('jumlahData')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Rentang Hari ke Belakang</label>
                <input type="number" wire:model="rentangHari" class="mt-1 w-full border rounded-lg px-3 py-2"
                    min="1" max="365" />
                @error('rentangHari')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <button wire:click="runSeed" wire:confirm="Yakin? Semua data akan dihapus dan dibuat ulang!"
                class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg">
                🔄 Reset & Seed Database
            </button>

            @if ($status === 'error')
                <div class="p-3 rounded-lg text-sm bg-red-100 text-red-800">
                    ❌ {{ $message }}
                </div>
            @endif
        @elseif ($status === 'running')
            <div class="flex flex-col items-center py-6 space-y-3" wire:poll.750ms="checkStatus">
                <svg class="animate-spin h-10 w-10 text-blue-500" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                </svg>
                <p class="text-gray-600 font-medium">Sedang proses, mohon tunggu...</p>
                <p class="text-gray-400 text-sm">migrate:fresh --seed sedang berjalan di background</p>
                {{-- Debug --}}
                <p class="text-red-500 text-xs font-mono">{{ $message }}</p>
            </div>
        @elseif ($status === 'done')
            <div class="p-4 bg-green-100 text-green-800 rounded-lg text-sm">
                ✅ {{ $message }}
            </div>
            <button wire:click="reset"
                class="w-full bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2 px-4 rounded-lg">
                Kembali
            </button>

        @endif
    </div>
</div>
