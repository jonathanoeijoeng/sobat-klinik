<x-header header="Farmasi"
    description="Modul pengelolaan resep masuk, validasi stok obat, dan finalisasi penyerahan obat kepada pasien. <br>Terintegrasi langsung dengan SatuSehat untuk pelaporan <b>MedicationRequest</b> dan <b>MedicationDispense</b> secara real-time." />

<div class="mt-0 md:mt-4 mb-8 block md:flex justify-between items-center">
    <div class='flex justify end order-1 md:order-2'>
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
    <div class='w-md order-2 md:order-1'>
        <x-input wire:model.live.debounce.300ms="search" icon="search" placeholder="Cari nama pasien..." name="search"
            type="search" />
    </div>
</div>
