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
            class="card mb-4 border-l-8 border-l-orange-500 shadow-sm bg-white dark:bg-gray-800 overflow-hidden">
            <div class="card-header bg-slate-50 dark:bg-gray-700/50 flex justify-between items-center p-4">
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="font-bold text-slate-800 dark:text-white text-lg">
                            {{ $invoice->outpatient_visit->patient->name }}
                        </h3>
                        <span class="text-xs font-mono bg-slate-200 px-2 py-0.5 rounded text-slate-600">
                            #{{ $invoice->invoice_number }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">
                        Dokter: {{ $invoice->outpatient_visit->practitioner->name ?? '-' }} |
                        Jam: {{ $invoice->created_at->format('H:i') }}
                    </p>
                </div>

                <div class="flex items-center gap-6">
                    <div class="text-right">
                        <p class="text-xs text-slate-400 uppercase font-semibold">Total Tagihan</p>
                        <p class="text-xl font-black text-blue-600">
                            IDR {{ number_format($invoice->grand_total, 0, ',', ',') }}
                        </p>
                    </div>

                    <x-button wire:click="confirmPayment({{ $invoice->id }})" variant="orange"
                        class="font-bold py-3 px-6">
                        PROSES BAYAR
                    </x-button>
                </div>
            </div>

            <div class="card-body p-0">
                <table class="w-full text-sm text-left">
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr>
                            <td class="px-6 py-2 text-slate-600 dark:text-gray-300">Biaya Layanan & Jasa Medis</td>
                            <td class="px-6 py-2 text-right font-medium">
                                IDR
                                {{ number_format($invoice->registration_fee + $invoice->practitioner_fee, 0, ',', ',') }}
                            </td>
                        </tr>
                        @if ($invoice->medicine_total > 0)
                            <tr>
                                <td class="px-6 py-2 text-slate-600 dark:text-gray-300">
                                    Farmasi / Obat ({{ $invoice->outpatient_visit->prescriptions->count() }} item)
                                </td>
                                <td class="px-6 py-2 text-right font-medium text-slate-600">
                                    IDR {{ number_format($invoice->medicine_total, 0, ',', ',') }}
                                </td>
                            </tr>
                        @else
                            <tr>
                                <td colspan="2" class="px-6 py-2 text-slate-400 italic text-center bg-slate-50/50">
                                    Kunjungan Non-Obat / Tebus Luar
                                </td>
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

    <x-modal wire:model="showConfirmPayment">
        @if ($showConfirmPayment)
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-bold text-slate-800">Proses Pembayaran</h2>
                </div>

                <div class="bg-slate-50 p-4 rounded-xl mb-6 border border-slate-100">
                    <p class="text-sm text-slate-600 leading-relaxed">
                        {!! $this->message !!}
                    </p>
                    <p class="text-sm text-slate-600 mt-2">
                        TOTAL: IDR <span class="font-bold text-xl text-blue-600">{{ $this->amount }}</span>
                    </p>
                    <p class="text-xs text-slate-400 mt-4 italic font-light">
                        *Pastikan pasien sudah melakukan pembayaran sebelum klik metode di bawah ini.
                    </p>
                </div>

                <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-3">
                    Klik Metode untuk Selesaikan:
                </p>

                <div class="grid grid-cols-2 gap-3">
                    @php
                        $paymentMethods = [
                            'QRIS' => 'brand',
                            'Cash' => 'zinc',
                            'Debit' => 'orange',
                            'CC' => 'blue',
                        ];
                    @endphp

                    @foreach ($paymentMethods as $m => $variant)
                        <x-button {{-- Klik tombol ini langsung menjalankan processPayment --}} wire:click="processPayment('{{ $m }}')"
                            wire:loading.attr="disabled" variant="{{ $variant }}"
                            class="flex flex-col items-center justify-center py-6 rounded-2xl border-2 border-transparent hover:border-slate-200 transition-all shadow-sm">
                            <span class="text-lg font-black">{{ $m }}</span>
                        </x-button>
                    @endforeach
                </div>
                @if ($lastPaymentMethod)
                    <div class="border-t border-slate-200 mt-4 pt-2 text-xs text-slate-500 italic">
                        Pembayaran terakhir menggunakan: <span class="font-bold">{{ $lastPaymentMethod }}</span>
                    </div>
                @endif

                <button wire:click="$set('showConfirmPayment', false)"
                    class="w-full mt-6 text-xs text-slate-400 hover:text-slate-600 underline">
                    Batal / Tutup
                </button>
            </div>
        @endif
    </x-modal>


    {{-- MODAL KONFIRMASI --}}
</div>
