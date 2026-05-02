<?php

use Livewire\Component;
use App\Models\Invoice;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use App\Jobs\FinalizeVisitJob;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public $selectedInvoice;
    public $showConfirmPayment = false;
    public $selectedMethod = 'Cash'; // Default method
    public $message = '';
    public $amount = 0;
    public $lastPaymentMethod = '';
    public $currentRoute;

    public function mount()
    {
        // Simpan nama route saat halaman pertama kali dibuka
        $this->currentRoute = request()->route()->getName();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    /**
     * TAHAP 1: Menyiapkan Data & Membuka Modal
     */
    // Method ini dipanggil saat klik tombol di KARTU
    public function confirmPayment($invoiceId)
    {
        $this->selectedInvoice = Invoice::with(['outpatient_visit.patient', 'outpatient_visit.prescriptions'])->findOrFail($invoiceId);

        $patientName = $this->selectedInvoice->outpatient_visit->patient->name;
        $total = number_format($this->selectedInvoice->grand_total, 0, ',', ',');
        $this->lastPaymentMethod = Invoice::whereHas('outpatient_visit', function ($query) {
            $query->where('patient_id', $this->selectedInvoice->outpatient_visit->patient_id);
        })
            ->where('payment_status', 'paid')
            ->whereNotNull('payment_method')
            ->latest('paid_at') // Urutkan berdasarkan waktu bayar terbaru
            ->value('payment_method'); // Ambil hanya nilai kolom payment_method

        $this->message = "Pilih metode pembayaran untuk pasien <b>{$patientName}</b>";
        $this->amount = $total;

        $this->showConfirmPayment = true;
    }

    // Method ini dipanggil saat klik metode di dalam MODAL
    public function processPayment($method)
    {
        $invoice = $this->selectedInvoice;

        if (!$invoice) {
            return;
        }

        $shouldFinalize = false;

        try {
            DB::transaction(function () use ($invoice, $method, &$shouldFinalize) {
                $invoice->update([
                    'payment_status' => 'paid',
                    'payment_method' => $method,
                    'paid_at' => now(),
                ]);

                $hasInternal = $invoice->outpatient_visit->prescriptions->where('status', '!==', 'external')->where('qty_ordered', '>', 0)->count() > 0;

                if (!$hasInternal) {
                    $invoice->outpatient_visit->update([
                        'internal_status' => 'finished',
                        'paid_at' => now(),
                    ]);
                    $shouldFinalize = true;
                } else {
                    $invoice->outpatient_visit->update(['internal_status' => 'paid']);
                }

                $invoice->outpatient_visit->prescriptions()->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);
            });

            if ($shouldFinalize) {
                FinalizeVisitJob::dispatch($invoice->outpatient_visit)->onQueue('high-priority');
            }

            $this->dispatch('toast', text: "Pembayaran {$method} Berhasil", type: 'success');
            $this->showConfirmPayment = false;
            $this->reset('selectedInvoice');
        } catch (\Exception $e) {
            $this->dispatch('toast', text: 'Error: ' . $e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        $lists = Invoice::query()
            ->with([
                'outpatient_visit.patient',
                'outpatient_visit.practitioner',
                'outpatient_visit.prescriptions', // Penting untuk performa loop
            ])
            ->where('payment_status', 'unpaid')
            ->whereHas('outpatient_visit', function ($query) {
                $query->where('internal_status', 'sent_for_payment');
            })
            ->when($this->search, function ($query) {
                $query->whereHas('outpatient_visit.patient', function ($q) {
                    $q->where('name', 'ilike', '%' . $this->search . '%');
                });
            })
            ->latest()
            ->paginate(25);

        return $this->view([
            'lists' => $lists,
        ]);
    }
};
?>

<div>
    @include('pages.cashier.route')

    @foreach ($lists as $invoice)
        <div wire:key="inv-{{ $invoice->id }}"
            class="card rounded-xl mb-6 border-l-8 border-l-orange-500 shadow-sm bg-white dark:bg-gray-800 overflow-hidden">

            {{-- Header: Fokus pada Nama dan Nominal di Mobile --}}
            <div class="card-header bg-slate-50 dark:bg-gray-700/50 p-4">
                <div class="flex flex-col md:flex-row justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="font-bold text-slate-800 dark:text-white text-lg leading-tight">
                                {{ $invoice->outpatient_visit->patient->name }}
                            </h3>
                            <span
                                class="text-[10px] font-mono bg-slate-200 dark:bg-gray-600 px-2 py-0.5 rounded text-slate-600 dark:text-gray-300">
                                #{{ $invoice->invoice_number }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">
                            <i class="fa-solid fa-user-md mr-1"></i>
                            {{ $invoice->outpatient_visit->practitioner->name ?? '-' }}
                            <span class="mx-1 text-slate-300">|</span>
                            <i class="fa-regular fa-clock mr-1"></i> {{ $invoice->created_at->format('H:i') }}
                        </p>
                    </div>

                    {{-- Section Harga: Menonjol di Mobile & Desktop --}}
                    <div
                        class="flex flex-row md:flex-row items-center justify-between md:gap-6 bg-blue-50 dark:bg-blue-900/20 p-3 md:p-0 rounded-lg md:bg-transparent">
                        <div class="text-left md:text-right">
                            <p class="text-[10px] text-blue-500 md:text-slate-400 uppercase font-bold tracking-tighter">
                                Total Tagihan</p>
                            <p
                                class="text-xl md:text-2xl font-mono font-black text-blue-600 dark:text-blue-400 leading-none">
                                IDR {{ number_format($invoice->grand_total, 0, ',', ',') }}
                            </p>
                        </div>

                        <x-button wire:click="confirmPayment({{ $invoice->id }})" variant="orange"
                            class="font-black py-3 px-6 shadow-md md:ml-4">
                            BAYAR
                        </x-button>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                {{-- Detail Table: Responsif --}}
                <table class="w-full text-xs md:text-sm text-left">
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-gray-700/30">
                            <td class="px-4 md:px-6 py-2.5 text-slate-600 dark:text-gray-400">Jasa Medis & Layanan</td>
                            <td
                                class="px-4 md:px-6 py-2.5 text-right font-medium text-slate-800 dark:text-gray-200 font-mono">
                                IDR
                                {{ number_format($invoice->registration_fee + $invoice->practitioner_fee, 0, ',', ',') }}
                            </td>
                        </tr>
                        @if ($invoice->medicine_total > 0)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-gray-700/30">
                                <td class="px-4 md:px-6 py-2.5 text-slate-600 dark:text-gray-400">
                                    Obat-obatan <span
                                        class="text-[10px] bg-blue-100 dark:bg-blue-900 text-blue-600 px-1.5 py-0.5 rounded ml-1">{{ $invoice->outpatient_visit->prescriptions->count() }}
                                        item</span>
                                </td>
                                <td
                                    class="px-4 md:px-6 py-2.5 text-right font-medium text-slate-800 dark:text-gray-200 font-mono">
                                    IDR {{ number_format($invoice->medicine_total, 0, ',', ',') }}
                                </td>
                            </tr>
                        @else
                            <tr>
                                <td colspan="2"
                                    class="px-6 py-3 text-slate-400 italic text-center bg-slate-50/30 dark:bg-gray-800/50 text-[11px]">
                                    <i class="fa-solid fa-pills mr-1"></i> Tidak ada tagihan obat / tebus luar.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $lists->links() }}
    </div>

    {{-- Modal Pembayaran: Dioptimalkan untuk Jempol (Touch Friendly) --}}
    <x-modal wire:model="showConfirmPayment">
        @if ($showConfirmPayment)
            <div class="p-6">
                <div class="text-center mb-6">
                    <h2 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tight">Proses
                        Pembayaran</h2>
                    <div class="h-1 w-20 bg-orange-500 mx-auto mt-2 rounded-full"></div>
                </div>

                <div class="bg-blue-600 p-6 rounded-2xl mb-8 shadow-inner text-center">
                    <p class="text-blue-100 text-xs uppercase font-bold tracking-widest mb-1">Total yang harus dibayar:
                    </p>
                    <h3 class="text-3xl font-black text-white">
                        IDR {{ $this->amount }}
                    </h3>
                </div>

                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-4 text-center">
                    Pilih Metode Pembayaran:
                </p>

                <div class="grid grid-cols-2 gap-4">
                    @php
                        $paymentMethods = [
                            'QRIS' => 'brand',
                            'Cash' => 'zinc',
                            'Debit' => 'orange',
                            'CC' => 'blue',
                        ];
                    @endphp

                    @foreach ($paymentMethods as $m => $variant)
                        <x-button wire:click="processPayment('{{ $m }}')" wire:loading.attr="disabled"
                            variant="{{ $variant }}"
                            class="flex flex-col items-center justify-center py-8 rounded-2xl border-b-4 border-black/10 active:border-b-0 transition-all">
                            <span class="text-xl font-black">{{ $m }}</span>
                        </x-button>
                    @endforeach
                </div>

                <button wire:click="$set('showConfirmPayment', false)"
                    class="w-full mt-8 py-2 text-sm font-bold text-slate-400 hover:text-rose-500 transition-colors">
                    BATALKAN TRANSAKSI
                </button>
            </div>
        @endif
    </x-modal>
</div>
