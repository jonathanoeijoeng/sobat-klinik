<x-header header="Kasir"
    description="Modul pengelolaan tagihan (Invoice), validasi transaksi, dan finalisasi penyerahan obat. Mendukung pencatatan pendapatan per visit serta pelaporan MedicationDispense dan Financial Resource ke SatuSehat secara real-time." />

<div class="mt-4 mb-8 flex justify-between items-center">
    <div class='w-md'>
        <x-input wire:model.live.debounce.300ms="search" icon="search" placeholder="Cari nama pasien..." name="search"
            type="search" />
    </div>
    <div class='flex justify end'>
        <div class="flex bg-gray-100 p-1 rounded-lg my-4 md:my-0 w-fit">
            <a href="{{ route('cashier.index') }}" wire:navigate
                class="px-4 py-2 rounded-md text-sm font-medium transition {{ $currentRoute === 'cashier.index' ? 'bg-white shadow text-brand-600' : 'text-gray-500 hover:text-gray-700' }}">
                Proses Pembayaran
            </a>

            <a href="{{ route('cashier.rekap') }}" wire:navigate
                class="px-4 py-2 rounded-md text-sm font-medium transition {{ $currentRoute === 'cashier.rekap' ? 'bg-white shadow text-brand-600' : 'text-gray-500 hover:text-gray-700' }}">
                Rekap Kasir
            </a>
        </div>
    </div>
</div>
