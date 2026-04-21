<x-header header="Farmasi"
    description="Modul pengelolaan resep masuk, validasi stok obat, dan finalisasi penyerahan obat kepada pasien. Terintegrasi langsung dengan SatuSehat untuk pelaporan MedicationDispense secara real-time." />

<div class="mt-4 mb-8 flex justify-between items-center">
    <div class='w-md'>
        <x-input wire:model.live.debounce.300ms="search" icon="search" placeholder="Cari nama pasien..." name="search"
            type="search" />
    </div>
    <div class='flex justify end'>
        <div class="flex bg-gray-100 p-1 rounded-lg my-4 md:my-0 w-fit">
            <a href="{{ route('pharmacy.index') }}" wire:navigate
                class="px-4 py-2 rounded-md text-sm font-medium transition {{ $currentRoute === 'pharmacy.index' ? 'bg-white shadow text-brand-600' : 'text-gray-500 hover:text-gray-700' }}">
                Penerimaan Resep
            </a>

            <a href="{{ route('pharmacy.dispensing') }}" wire:navigate
                class="px-4 py-2 rounded-md text-sm font-medium transition {{ $currentRoute === 'pharmacy.dispensing' ? 'bg-white shadow text-brand-600' : 'text-gray-500 hover:text-gray-700' }}">
                Penyerahan Obat
            </a>
        </div>
    </div>
</div>
