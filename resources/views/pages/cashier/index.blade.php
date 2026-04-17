<?php

use Livewire\Component;
use App\Models\Invoice;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public $selectedInvoice;
    public $paymentMethod = 'cash'; // default
    public $showModal = false;

    // Pastikan menambahkan 'visit.prescriptions' di query utama agar data siap

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function confirmPayment($invoiceId)
    {
        $this->selectedInvoice = Invoice::with('visit')->findOrFail($invoiceId);
        $this->showModal = true;
    }

    public function processPayment()
    {
        // Gunakan with agar relasi ikut terload, mengurangi query di dalam transaksi
        $invoice = Invoice::with(['visit', 'visit.prescriptions'])->findOrFail($this->selectedInvoice->id);

        // Validasi status
        if ($invoice->payment_status !== 'unpaid' || $invoice->visit->internal_status !== 'sent_for_payment') {
            $this->dispatch('toaster', message: 'Invoice atau visit tidak valid.', type: 'error');
            return;
        }

        try {
            // PENTING: Tambahkan 'use ($invoice)' agar variabel bisa masuk ke dalam closure
            DB::transaction(function () use ($invoice) {
                // 1. Update status invoice
                $invoice->update([
                    'payment_status' => 'paid',
                    'paid_at' => now(),
                ]);

                // 2. Update status visit
                $invoice->visit->update([
                    'internal_status' => 'paid',
                    'paid_at' => now(),
                ]);

                // 3. Update status resep (Mass Update)
                $invoice->visit->prescriptions()->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);
            });

            $this->dispatch('toaster', message: 'Pembayaran berhasil diproses.', type: 'success');
        } catch (\Exception $e) {
            // Jika gagal di Intel NUC Anda, error akan tertangkap di sini
            $this->dispatch('toaster', message: 'Gagal memproses pembayaran: ' . $e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        $lists = Invoice::query()
            ->with([
                'visit.patient',
                'visit.practitioner', // Tambahkan ini jika ingin menampilkan nama dokter di tabel kasir
            ])
            ->where('payment_status', 'unpaid')
            ->whereHas('visit', function ($query) {
                $query->where('internal_status', 'sent_for_payment');
            })
            ->when($this->search, function ($query) {
                // Optimasi: Gabungkan pencarian nama pasien atau nomor rekam medis jika perlu
                $query->whereHas('visit.patient', function ($q) {
                    $q->where('name', 'ilike', '%' . $this->search . '%');
                });
            })
            ->latest() // Menggunakan created_at secara default
            ->paginate(25);

        return $this->view([
            'lists' => $lists,
        ]);
    }
};
?>

<div>
    <x-header header="Kasir"
        description="Kelola seluruh transaksi layanan kesehatan, validasi rincian biaya dari dokter dan farmasi, serta proses pembayaran pasien secara akurat untuk menyelesaikan kunjungan" />

    @foreach ($lists as $invoice)
        @php
            // Logika border berdasarkan internal_status visit
            $internalStatus = $invoice->visit->internal_status;
            $statusBorder = 'border-l-gray-300';

            if ($internalStatus === 'sent_for_payment') {
                $statusBorder = 'border-l-orange-500'; // Menunggu Bayar
            } elseif ($internalStatus === 'paid') {
                $statusBorder = 'border-l-emerald-500'; // Sudah Bayar
            }
        @endphp

        <div class="card mb-4 border-l-8 {{ $statusBorder }} shadow-sm bg-white dark:bg-gray-800 overflow-hidden">
            <div class="card-header bg-slate-50 dark:bg-gray-700/50 flex justify-between items-center p-4">
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="font-bold text-slate-800 dark:text-white text-lg">
                            {{ $invoice->visit->patient->name }}
                        </h3>
                        <span class="text-xs font-mono bg-slate-200 px-2 py-0.5 rounded text-slate-600">
                            #{{ $invoice->invoice_number }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">
                        Dokter: {{ $invoice->visit->practitioner->name ?? '-' }} |
                        Antre sejak: {{ $invoice->created_at->format('H:i') }}
                        ({{ $invoice->created_at->diffForHumans() }})
                    </p>
                </div>

                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <p class="text-xs text-slate-400 uppercase font-semibold">Total Tagihan</p>
                        <p class="text-xl font-bold text-blue-600">
                            IDR {{ number_format($invoice->grand_total, 0, ',', ',') }}
                        </p>
                    </div>

                    <x-button wire:click="processPayment({{ $invoice->id }})" variant="primary" class="font-bold">
                        PROSES BAYAR
                    </x-button>
                </div>
            </div>

            <div class="card-body p-0">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-slate-400 uppercase bg-slate-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-2">Item Layanan</th>
                            <th class="px-6 py-2 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        {{-- Baris Registrasi --}}
                        <tr>
                            <td class="px-6 py-3 text-slate-600 dark:text-gray-300">Biaya Registrasi & Administrasi</td>
                            <td class="px-6 py-3 text-right font-medium">
                                IDR {{ number_format($invoice->registration_fee, 0, ',', ',') }}
                            </td>
                        </tr>
                        {{-- Baris Jasa Medis --}}
                        <tr>
                            <td class="px-6 py-3 text-slate-600 dark:text-gray-300">Jasa Medis (Practitioner Fee)</td>
                            <td class="px-6 py-3 text-right font-medium">
                                IDR {{ number_format($invoice->practitioner_fee, 0, ',', ',') }}
                            </td>
                        </tr>
                        {{-- Baris Obat-obatan --}}
                        @if ($invoice->medicine_total > 0)
                            <tr>
                                <td class="px-6 py-3 text-slate-600 dark:text-gray-300 flex gap-4 items-center">
                                    <span>Obat-obatan / Farmasi</span>
                                    <span class="text-xs text-slate-400 italic">
                                        {{ $invoice->visit->prescriptions->count() }}
                                        item(s) </span>
                                </td>
                                <td class="px-6 py-3 text-right font-medium text-slate-600">
                                    IDR {{ number_format($invoice->medicine_total, 0, ',', ',') }}
                                </td>
                            </tr>
                        @else
                            <tr>
                                <td class="px-6 py-3 text-slate-400 italic">Tidak ada penebusan obat (Tebus Luar)</td>
                                <td class="px-6 py-3 text-right font-medium text-slate-400">IDR 0</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

    <div class="mt-4">
        {{ $lists->links() }}
    </div>

    <x-modal wire:model="showModal">
        <div class="p-6">
            <h2 class="text-lg font-bold mb-4 text-slate-800">Konfirmasi Pembayaran</h2>

            <div class="bg-slate-50 p-4 rounded-lg mb-6">
                <p class="text-sm text-slate-500">Total yang harus dibayar:</p>
                <p class="text-2xl font-black text-blue-600">
                    IDR {{ number_format($selectedInvoice?->total_amount, 0, ',', ',') }}
                </p>
            </div>

            <div class="space-y-4">
                <label class="block text-sm font-medium text-gray-700">Pilih Metode Pembayaran:</label>
                <div class="grid grid-cols-2 gap-4">
                    <label
                        class="relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm focus:outline-none">
                        <input type="radio" wire:model="paymentMethod" value="cash" class="sr-only">
                        <span class="flex flex-1">
                            <span class="flex flex-col">
                                <span class="block text-sm font-medium text-gray-900">TUNAI</span>
                            </span>
                        </span>
                        <x-icon.check x-show="$wire.paymentMethod == 'cash'" class="h-5 w-5 text-blue-600" />
                    </label>

                    <label
                        class="relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm focus:outline-none">
                        <input type="radio" wire:model="paymentMethod" value="qris" class="sr-only">
                        <span class="flex flex-1">
                            <span class="flex flex-col">
                                <span class="block text-sm font-medium text-gray-900">QRIS / TRANSFER</span>
                            </span>
                        </span>
                        <x-icon.check x-show="$wire.paymentMethod == 'qris'" class="h-5 w-5 text-blue-600" />
                    </label>
                </div>
            </div>

            <div class="mt-8 flex justify-end gap-3">
                <x-button wire:click="$set('showModal', false)" variant="ghost">Batal</x-button>
                <x-button wire:click="confirmPayment" variant="primary" class="px-8">
                    KONFIRMASI LUNAS
                </x-button>
            </div>
        </div>
    </x-modal>
</div>
