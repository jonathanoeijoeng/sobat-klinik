<?php

use Livewire\Component;
use App\Models\Medicine;
use App\Services\SatuSehatService;

new class extends Component {
    public $searchQuery = '';
    public $kfaResults = [];
    public $showModal = false;
    public $showEditModal = false;
    public $het_price;
    public $editingId = null;

    public function edit($id)
    {
        // Logika untuk edit obat (bisa buka modal dengan data obat yang dipilih)
        $medicine = Medicine::findOrFail($id);
        $this->showEditModal = true;
        $this->het_price = $medicine->het_price;
        $this->editingId = $id;

        // Implementasi edit sesuai kebutuhan, misal buka modal dengan data $medicine
    }

    public function updateHarga()
    {
        // Logika untuk menyimpan perubahan harga obat
        if ($this->editingId) {
            $medicine = Medicine::findOrFail($this->editingId);
            $medicine->update(['het_price' => $this->het_price]);
            $this->dispatch('notify', message: 'Harga obat berhasil diperbarui!', type: 'success');
        }
        $this->closeModal();
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->showEditModal = false;
        $this->reset('het_price', 'editingId');
    }

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
        <table class="max-w-full divide-y divide-gray-200">
            <thead class="bg-brand-500">
                <tr>
                    <th class=" px-6 py-4 text-left text-sm font-bold text-white uppercase">
                        Nama Obat
                    </th>
                    <th class=" px-6 py-4 text-left text-sm font-bold text-white uppercase tracking-widest">
                        Kode KFA</th>
                    <th class="w-px px-6 py-4 text-center text-sm font-bold text-white uppercase tracking-widest">
                        SatuSehat ID</th>
                    <th class=" px-6 py-4 text-center text-sm font-bold text-white uppercase tracking-widest">
                        Pabrikan</th>
                    <th class=" px-6 py-4 text-right text-sm font-bold text-white uppercase tracking-widest">
                        Price</th>
                    <th class="px-12 py-4 text-right text-sm font-bold text-white uppercase tracking-widest">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach ($medicines as $medicine)
                    <tr class="w-fit">
                        <td class="px-6 py-4">
                            <div class=" text-gray-900">{{ $medicine->name }}</div>
                        </td>
                        <td class=" px-6 py-4">
                            <div class=" text-gray-900">{{ $medicine->kfa_code }}</div>
                        </td>
                        <td class=" px-6 py-4 text-center text-sm truncate">
                            {{ $medicine->satusehat_medication_id }}</td>
                        <td class=" px-6 py-4 text-center text-sm">
                            {{ $medicine->manufacturer }}</td>
                        <td class=" px-6 py-4 text-right font-mono text-sm">
                            {{ number_format($medicine->het_price, 0, ',', '.') }}</td>
                        <td class="px-12 py-4 text-right text-sm">
                            <x-edit wire:click="edit({{ $medicine->id }})" />
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

    {{-- Edit Modal --}}
    <div x-data="{ isOpen: @entangle('showEditModal') }" x-show="isOpen" @keydown.escape.window="isOpen = false"
        class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">

        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"></div>

        <div class="flex items-center justify-center min-h-screen p-4">
            <div @click.away="isOpen = false"
                class="relative bg-white dark:bg-zinc-800 w-full max-w-md rounded-2xl shadow-xl overflow-hidden transform transition-all">

                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold dark:text-white uppercase tracking-widest">
                            Edit Harga Obat
                        </h3>
                    </div>

                    <div class="space-y-4">
                        {{-- Menggunakan Komponen Reusable --}}
                        <x-input label="Harga Obat" name="het_price" wire:model="het_price" />
                    </div>
                </div>
                <form wire:submit.prevent="save">

                    <div class="p-4 bg-gray-50 dark:bg-zinc-800/50 flex justify-end gap-3">
                        <x-button variant="zinc" type="submit" wire:click="closeModal">
                            Cancel
                        </x-button>
                        <x-button variant="brand" wire:click="updateHarga" loading="save">
                            Save
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
