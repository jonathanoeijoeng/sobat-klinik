<?php

use Livewire\Component;
use App\Models\Medicine;
use App\Services\SatuSehatService;

new class extends Component {
    public $searchQuery = '';
    public $kfaResults = [];
    public $showModal = false;

    public function updatedSearchQuery()
    {
        if (strlen($this->searchQuery) > 3) {
            $res = app(SatuSehatService::class)->searchKFA($this->searchQuery);
            $rawData = $res['items']['data'] ?? [];

            // HANYA ambil field yang mau ditampilkan di tabel modal
            $this->kfaResults = collect($rawData)
                ->map(function ($item) {
                    return [
                        'kfa_code' => $item['kfa_code'],
                        'name' => $item['name'],
                        'manufacturer' => $item['manufacturer'] ?? '-',
                        'nie' => $item['nie'] ?? '-',
                    ];
                })
                ->toArray();
        }
    }

    // Di Component Livewire
    public function selectAndSync($kfaCode)
    {
        try {
            // Ambil ulang data dari array pencarian yang sudah disimpan di memory/state
            $item = collect($this->kfaResults)->firstWhere('kfa_code', $kfaCode);

            if (!$item) {
                return;
            }

            // Simpan ke DB Lokal
            $medicine = Medicine::updateOrCreate(
                ['kfa_code' => $kfaCode],
                [
                    'name' => $item['name'],
                    'display_name' => $item['name'],
                    'manufacturer' => $item['manufacturer'] ?? null,
                    'fix_price' => $item['fix_price'] ?? null,
                ],
            );

            // Sync ke SatuSehat
            $this->syncMedication($medicine->id);

            // RESET DAN TUTUP MODAL
            $this->reset(['searchQuery', 'kfaResults']);
            $this->showModal = false; // Pastikan ini diset false
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            $this->dispatch('notify', message: 'Terjadi kesalahan!', type: 'error');
        }
    }

    public function syncMedication($id)
    {
        $medicine = Medicine::findOrFail($id);

        if (!$medicine->kfa_code) {
            $this->dispatch('notify', message: 'Kode KFA wajib diisi!', type: 'error');
            return;
        }

        $service = app(SatuSehatService::class);
        $response = $service->createMedication($medicine);

        if (isset($response['id'])) {
            $medicine->update([
                'satusehat_medication_id' => $response['id'],
                'last_synced_at' => now(),
            ]);
            $this->dispatch('notify', message: 'Obat berhasil didaftarkan!', type: 'success');
        } else {
            // Log error jika gagal untuk debugging di server
            \Log::error("Gagal Sync Obat ID {$id}: ", $response);
            $this->dispatch('notify', message: 'Gagal: ' . ($response['issue'][0]['details']['text'] ?? 'Error tidak diketahui'), type: 'error');
        }
    }

    public function render()
    {
        return $this->view([
            'medicines' => Medicine::paginate(10),
        ]);
    }
};
?>

<div>
    <x-header header="Master Data Obat" description="Daftar obat yang sudah diverifikasi dan mendapatkan ID satusehat" />

    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
        <x-input wire:model.live.debounce.100ms="search" name="search" placeholder="Cari obat..."
            class="mb-4 md:max-w-lg w-full" />
        <x-button wire:click="showModal = true" class="mb-4" color="brand">Registrasi Baru</x-button>
    </div>

    <div class="border rounded-lg overflow-x-auto shadow-sm -mx-4 px-4 md:mx-0 md:px-0">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-brand-500">
                <tr>
                    <th
                        class="w-px whitespace-nowrap px-6 py-4 text-left text-sm font-bold text-white uppercase tracking-widest">
                        Nama Obat
                    </th>
                    <th
                        class="w-px whitespace-nowrap px-6 py-4 text-left text-sm font-bold text-white uppercase tracking-widest">
                        Kode KFA</th>
                    <th
                        class="w-px whitespace-nowrap px-6 py-4 text-center text-sm font-bold text-white uppercase tracking-widest">
                        SatuSehat ID</th>
                    <th
                        class="w-px whitespace-nowrap px-6 py-4 text-center text-sm font-bold text-white uppercase tracking-widest">
                        Pabrikan</th>
                    <th
                        class="w-px whitespace-nowrap px-6 py-4 text-center text-sm font-bold text-white uppercase tracking-widest">
                        Status</th>
                    <th class="px-12 py-4 text-right text-sm font-bold text-white uppercase tracking-widest">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach ($medicines as $medicine)
                    <tr>
                        <td class="w-px whitespace-nowrap px-6 py-4">
                            <div class=" text-gray-900">{{ $medicine->name }}</div>
                        </td>
                        <td class="w-px whitespace-nowrap px-6 py-4">
                            <div class=" text-gray-900">{{ $medicine->kfa_code }}</div>
                        </td>
                        <td class="w-px whitespace-nowrap px-6 py-4 text-center text-sm">
                            {{ $medicine->satusehat_medication_id }}</td>
                        <td class="w-px whitespace-nowrap px-6 py-4 text-center text-sm">
                            {{ $medicine->manufacturer }}</td>
                        <td class="px-12 py-4 text-right text-sm">
                            <button class="text-blue-600 hover:text-blue-900">Detail</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="md:block hidden mt-4">
        {{ $medicines->links() }}
    </div>
    <div x-data="{ open: @entangle('showModal') }">
        <div x-show="open" class="fixed inset-0 bg-gray-400 bg-opacity-25 transition-opacity"></div>

        <div x-show="open" class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div
                    class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 mx-auto w-7xl p-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Cari Obat di Database KFA</h3>

                    <x-input type="text" wire:model.live.debounce.500ms="searchQuery" name="searchQuery"
                        placeholder="Ketik nama obat (min. 4 karakter)..." />

                    <div class="mt-4 max-h-60 overflow-y-auto">
                        @forelse($kfaResults as $item)
                            <div class="flex justify-between items-center p-3 border-b hover:bg-gray-50">
                                <div>
                                    <p class="text-sm font-bold text-gray-800">{{ $item['name'] }}</p>
                                    <p class="text-xs text-gray-500">KFA: {{ $item['kfa_code'] }} </p>
                                    <p class="text-xs text-gray-500">Manufaktur: {{ $item['manufacturer'] }} </p>
                                </div>
                                <button wire:click="selectAndSync({{ $item['kfa_code'] }})"
                                    class="bg-brand-600 text-white px-3 py-1 rounded text-xs hover:bg-brand-700">
                                    Pilih & Sync
                                </button>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 italic p-3 text-center">Belum ada hasil pencarian.</p>
                        @endforelse
                    </div>

                    <div class="mt-5 flex justify-end">
                        <button @click="open = false" class="text-gray-500 text-sm">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
