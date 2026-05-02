<?php

use Livewire\Component;
use App\Models\Invoice;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

new class extends Component {
    use WithPagination;
    public $currentRoute;
    public string $search = '';
    public $startDate;
    public $endDate;

    public function mount()
    {
        // Simpan nama route saat halaman pertama kali dibuka
        $this->currentRoute = request()->route()->getName();
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->endOfMonth()->format('Y-m-d');
    }

    public function paginationView()
    {
        return 'vendor.pagination.tailwind';
    }

    public function resetFilters()
    {
        $this->reset(['search']);
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->endOfMonth()->format('Y-m-d');
        $this->resetPage();
    }

    public function render()
    {
        $invoices = Invoice::with(['outpatient_visit.patient']) // Eager loading dalam array lebih rapi
            // ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->when($this->startDate && $this->endDate, function ($query) {
                $query->whereBetween('paid_at', [Carbon::parse($this->startDate)->startOfDay(), Carbon::parse($this->endDate)->endOfDay()]);
            })
            ->when($this->search, function ($query) {
                $query->where(function ($sub) {
                    // Pencarian di level Invoice
                    $sub->where('invoice_number', 'ilike', '%' . $this->search . '%')
                        // Pencarian di level Patient (Relasi)
                        ->orWhereHas('outpatient_visit.patient', function ($q) {
                            $q->where('name', 'ilike', '%' . $this->search . '%');
                        });
                });
            })
            ->orderBy('paid_at', 'desc') // Lebih eksplisit dibanding latest()
            ->paginate(25);
        $totals = Invoice::query()
            ->selectRaw('SUM(registration_fee) as total_reg')
            ->selectRaw('SUM(practitioner_fee) as total_practitioner')
            ->selectRaw('SUM(medicine_total) as total_medicine')
            ->selectRaw('SUM(grand_total) as total_grand')
            ->where('payment_status', 'paid') // Hanya yang sudah lunas
            ->when($this->startDate && $this->endDate, function ($query) {
                $query->whereBetween('paid_at', [Carbon::parse($this->startDate)->startOfDay(), Carbon::parse($this->endDate)->endOfDay()]);
            })
            ->when($this->search, function ($query) {
                $query->whereHas('outpatient_visit.patient', function ($q) {
                    $q->where('name', 'ilike', '%' . $this->search . '%');
                });
            })
            ->first();

        return $this->view([
            'invoices' => $invoices,
            'totals' => $totals,
        ]);
    }
};
?>

<div>
    @include('pages.cashier.route')

    {{-- Filter Tanggal: Dibuat Full Width di Mobile --}}
    <div class="flex flex-col md:flex-row justify-between gap-3 mb-4">
        <div
            class="flex items-center justify-between bg-white rounded-lg border border-gray-200 px-2 py-1 w-full md:w-fit shadow-sm">
            <div class="flex items-center gap-1">
                <x-input type="date" wire:model.live="startDate" class="border-none focus:ring-0 text-sm"
                    name="start_date" />
                <span class="text-gray-400 text-xs font-bold">s/d</span>
                <x-input type="date" wire:model.live="endDate" class="border-none focus:ring-0 text-sm"
                    name="end_date" />
            </div>

            @if ($startDate || $endDate)
                <button wire:click="resetFilters" class="text-red-500 hover:text-red-700 p-2">
                    <x-icon name="x-circle" class="w-5 h-5" />
                </button>
            @endif
        </div>

        {{-- Widget Total Cepat (Hanya muncul di mobile di bagian atas) --}}
        <div class="md:hidden bg-brand-500 p-3 rounded-lg text-white shadow-md flex justify-between items-center">
            <span class="text-xs font-bold uppercase tracking-wider">Total Rekap</span>
            <span class="text-lg font-black font-mono">IDR {{ number_format($totals->total_grand, 0, ',', ',') }}</span>
        </div>
    </div>

    {{-- VIEW DESKTOP: Tabel Tradisional --}}
    <div class="hidden md:block border rounded-lg overflow-hidden shadow-sm">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-brand-600">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-bold text-white uppercase tracking-widest">Tanggal</th>
                    <th class="px-4 py-3 text-left text-sm font-bold text-white uppercase tracking-widest">Nama</th>
                    <th class="px-4 py-3 text-center text-sm font-bold text-white uppercase tracking-widest">
                        Status/Method</th>
                    <th class="px-4 py-3 text-right text-sm font-bold text-white uppercase tracking-widest">Regis</th>
                    <th class="px-4 py-3 text-right text-sm font-bold text-white uppercase tracking-widest">Dokter</th>
                    <th class="px-4 py-3 text-right text-sm font-bold text-white uppercase tracking-widest">Obat</th>
                    <th class="px-4 py-3 text-right text-sm font-bold text-white uppercase tracking-widest">Total</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <tr class="bg-slate-100 font-black">
                    <td colspan="3" class="px-4 py-3 text-center text-sm uppercase tracking-widest">Total Keseluruhan
                    </td>
                    <td class="px-4 py-3 text-right font-mono text-sm">IDR
                        {{ number_format($totals->total_reg, 0, ',', ',') }}</td>
                    <td class="px-4 py-3 text-right font-mono text-sm">IDR
                        {{ number_format($totals->total_practitioner, 0, ',', ',') }}</td>
                    <td class="px-4 py-3 text-right font-mono text-sm">IDR
                        {{ number_format($totals->total_medicine, 0, ',', ',') }}</td>
                    <td class="px-4 py-3 text-right font-mono text-sm text-brand-600">IDR
                        {{ number_format($totals->total_grand, 0, ',', ',') }}</td>
                </tr>
                @forelse ($invoices as $invoice)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-4 py-3 text-sm">{{ Carbon::parse($invoice->created_at)->format('d/m/y') }}</td>
                        <td class="px-4 py-3 text-sm font-bold">{{ $invoice->outpatient_visit->patient->name }}</td>
                        <td class="px-4 py-3 text-center text-[10px]">
                            <span
                                class="px-2 py-0.5 rounded bg-green-100 text-green-700 font-bold uppercase">{{ $invoice->payment_method }}</span>
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-sm">
                            {{ number_format($invoice->registration_fee, 0, ',', ',') }}</td>
                        <td class="px-4 py-3 text-right font-mono text-sm">
                            {{ number_format($invoice->practitioner_fee, 0, ',', ',') }}</td>
                        <td class="px-4 py-3 text-right font-mono text-sm">
                            {{ number_format($invoice->medicine_total, 0, ',', ',') }}</td>
                        <td class="px-4 py-3 text-right font-mono text-sm font-bold">
                            {{ number_format($invoice->grand_total, 0, ',', ',') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-10 text-center"><x-nodatafound /></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- VIEW MOBILE: List Card --}}
    <div class="md:hidden space-y-3">
        @forelse ($invoices as $invoice)
            <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <p class="text-[10px] text-gray-400 font-bold uppercase leading-none">
                            {{ Carbon::parse($invoice->created_at)->format('d M Y') }}</p>
                        <h4 class="font-bold text-slate-800">{{ $invoice->outpatient_visit->patient->name }}</h4>
                    </div>
                    <span
                        class="bg-slate-100 text-slate-600 px-2 py-1 rounded text-[10px] font-black uppercase border border-slate-200">
                        {{ $invoice->payment_method }}
                    </span>
                </div>

                {{-- Breakdowns --}}
                <div class="grid grid-cols-3 gap-2 border-t border-b border-gray-50 py-2 mb-2">
                    <div class="text-center">
                        <p class="text-[9px] text-gray-400 uppercase">Regis</p>
                        <p class="text-xs font-mono">{{ number_format($invoice->registration_fee, 0, ',', ',') }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-[9px] text-gray-400 uppercase">Dokter</p>
                        <p class="text-xs font-mono">{{ number_format($invoice->practitioner_fee, 0, ',', ',') }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-[9px] text-gray-400 uppercase">Obat</p>
                        <p class="text-xs font-mono">{{ number_format($invoice->medicine_total, 0, ',', ',') }}</p>
                    </div>
                </div>

                <div class="flex justify-between items-center">
                    <span class="text-xs font-bold text-gray-500 uppercase">Total Tagihan</span>
                    <span class="text-sm font-black text-brand-600 font-mono">IDR
                        {{ number_format($invoice->grand_total, 0, ',', ',') }}</span>
                </div>
            </div>
        @empty
            <div class="bg-white p-10 rounded-lg border border-dashed border-gray-300 text-center">
                <x-nodatafound />
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $invoices->links() }}
    </div>
</div>
