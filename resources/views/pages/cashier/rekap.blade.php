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
            ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
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
    <div
        class="flex justify-between pr-2  gap-2 items-center bg-white rounded-lg border border-gray-200 mb-3 w-full md:w-fit">
        <div class="flex items-center gap-2">
            <x-input type="date" wire:model.live="startDate" class="border-none focus:ring-0" name="start_date" />
            <span class="text-gray-400">s/d</span>
            <x-input type="date" wire:model.live="endDate" class="border-none focus:ring-0" name="end_date" />
        </div>

        @if ($startDate || $endDate)
            <button wire:click="resetFilters" class="text-red-500 hover:text-red-700 p-1">
                <x-icon name="x-circle" class="w-5 h-5" />
            </button>
        @endif
    </div>

    <div class="border rounded-lg overflow-x-auto shadow-sm md:mx-0 md:px-0">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-brand-500">
                <tr>
                    <th
                        class="px-3 md:px-6 py-4 text-left text-xs md:text-sm font-bold text-white uppercase tracking-widest">
                        Tanggal
                    </th>
                    <th
                        class="px-3 md:px-6 py-4 text-left text-xs md:text-sm font-bold text-white uppercase tracking-widest">
                        Nama</th>
                    <th
                        class="px-3 md:px-6 py-4 text-center text-xs md:text-sm font-bold text-white uppercase tracking-widest">
                        Status</th>
                    <th
                        class="px-3 md:px-6 py-4 text-center text-xs md:text-sm font-bold text-white uppercase tracking-widest">
                        Method</th>
                    <th
                        class="px-3 md:px-6 py-4 text-right text-xs md:text-sm font-bold text-white uppercase tracking-widest">
                        Registration</th>
                    <th
                        class="px-3 md:px-6 py-4 text-right text-xs md:text-sm font-bold text-white uppercase tracking-widest">
                        Dokter</th>
                    <th
                        class="px-3 md:px-6 py-4 text-right text-xs md:text-sm font-bold text-white uppercase tracking-widest">
                        Obat</th>
                    <th
                        class="px-3 md:px-6 py-4 text-right text-xs md:text-sm font-bold text-white uppercase tracking-widest">
                        Total</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <tr class="bg-slate-200">
                    <td colspan="4"
                        class="px-3 md:px-6 py-4 text-center text-xs md:text-sm font-bold text-gray-900 uppercase tracking-widest">
                        Total
                    </td>
                    <td class="px-3 md:px-6 py-4 text-right font-mono text-xs md:text-sm font-bold">
                        IDR {{ number_format($totals->total_reg, 0, ',', ',') }}
                    </td>
                    <td class="px-3 md:px-6 py-4 text-right font-mono text-xs md:text-sm font-bold">
                        IDR {{ number_format($totals->total_practitioner, 0, ',', ',') }}
                    </td>
                    <td class="px-3 md:px-6 py-4 text-right font-mono text-xs md:text-sm font-bold">
                        IDR {{ number_format($totals->total_medicine, 0, ',', ',') }}
                    </td>
                    <td class="px-3 md:px-6 py-4 text-right font-mono text-xs md:text-sm font-bold">
                        IDR {{ number_format($totals->total_grand, 0, ',', ',') }}
                    </td>
                </tr>
                @forelse ($invoices as $invoice)
                    <tr>
                        <td class=" px-3 md:px-6 py-4 text-center text-xs md:text-sm  capitalize">
                            {{ Carbon::parse($invoice->created_at)->format('d M Y') }}
                        </td>
                        <td class=" px-3 md:px-6 py-4 text-xs md:text-sm">
                            <div class=" text-gray-900">{{ $invoice->outpatient_visit->patient->name }}</div>
                        </td>
                        <td class=" px-3 md:px-6 py-4 text-center text-xs md:text-sm uppercase">
                            {{ $invoice->payment_status }}
                        </td>
                        <td class=" px-3 md:px-6 py-4 text-center text-xs md:text-sm uppercase">
                            {{ $invoice->payment_method }}
                        </td>
                        <td class=" px-3 md:px-6 py-4 text-right font-mono text-xs md:text-sm">
                            IDR {{ number_format($invoice->registration_fee, 0, ',', ',') }}
                        </td>
                        <td class="px-3 md:px-6 py-4 text-right font-mono text-xs md:text-sm">
                            IDR {{ number_format($invoice->practitioner_fee, 0, ',', ',') }}
                        </td>
                        <td class="px-3 md:px-6 py-4 text-right font-mono text-xs md:text-sm">
                            IDR {{ number_format($invoice->medicine_total, 0, ',', ',') }}
                        </td>
                        <td class="px-3 md:px-6 py-4 text-right font-mono text-xs md:text-sm">
                            IDR {{ number_format($invoice->grand_total, 0, ',', ',') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-3 md:px-6 py-4 text-center text-xs md:text-sm font-medium">
                            <x-nodatafound />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="md:block hidden mt-4">
        {{ $invoices->links() }}
    </div>
</div>
