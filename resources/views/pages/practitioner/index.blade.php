<?php

use Livewire\Component;
use App\Models\OutpatientVisit;
use App\Models\Patient;
use App\Models\Practitioner;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Transaction;
use Illuminate\Support\Facades\Log;
use App\Jobs\SyncEncounterToSatuSehat;
use App\Services\SatuSehatService;

new class extends Component {
    use WithPagination;

    public function startConsultation($visitId)
    {
        $visit = OutpatientVisit::findOrFail($visitId);
        $service = app(SatuSehatService::class);

        // 1. Update jam mulai hanya jika masih kosong (biar tidak tertimpa kalau ke-refresh)
        if (!$visit->in_progress_at) {
            $visit->update([
                'in_progress_at' => now(),
                'status' => 'in-progress', // Opsional: jika kamu pakai kolom status
                'internal_status' => 'at_practitioner', // Untuk tracking internal
                'at_practitioner_at' => now(), // Timestamp untuk tracking internal
            ]);
        }

        $service->updateEncounterStatusAndDiagnosis($visit, 'in-progress');

        // 2. Redirect ke halaman diagnosis
        return redirect()->route('practitioner.diagnosis', $visit->id);
    }

    public function render()
    {
        $patients = OutpatientVisit::query()
            ->with('patient', 'vitalSign')
            ->whereIn('internal_status', ['arrived', 'at_practitioner'])
            ->latest()
            ->paginate(25);

        $practitioners = Practitioner::all();

        return $this->view([
            'patients' => $patients,
            'practitioners' => $practitioners,
        ]);
    }
};
?>

<div>
    <x-header header="Dokter"
        description="Dashboard utama bagi praktisi medis untuk memproses kunjungan pasien, meninjau hasil pemeriksaan tanda-tanda vital, dan memberikan instruksi klinis. <br>Pantau status sinkronisasi setiap <b>Resource ID</b> untuk menjamin kelengkapan dokumentasi medis pada setiap episode perawatan." />
    <div class="border rounded-lg overflow-x-auto shadow-sm -mx-4 px-4 md:mx-0 md:px-0">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-brand-500">
                <tr>
                    <th class="px-6 py-4 text-left text-sm font-bold text-white uppercase tracking-widest">
                        Nama
                    </th>
                    <th class="px-6 py-4 text-center text-sm font-bold text-white uppercase tracking-widest">
                        L/P</th>
                    <th class="px-6 py-4 text-center text-sm font-bold text-white uppercase tracking-widest">
                        Tekanan darah</th>
                    <th class="px-6 py-4 text-center text-sm font-bold text-white uppercase tracking-widest">
                        Tinggi/Berat badan</th>
                    <th class="px-6 py-4 text-center text-sm font-bold text-white uppercase tracking-widest">
                        Keluhan</th>
                    <th class="px-6 py-4 text-center text-sm font-bold text-white uppercase tracking-widest">
                        Status</th>
                    <th class="px-12 py-4 text-right text-sm font-bold text-white uppercase tracking-widest">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($patients as $visit)
                    <tr>
                        <td class=" px-6 py-4">
                            <div class="font-medium text-gray-900">{{ $visit->patient->name }}</div>
                        </td>
                        <td class=" px-6 py-4 text-center text-sm font-medium">
                            {{ $visit->gender === 'female' ? 'Wanita' : 'Pria' }}</td>
                        <td class=" px-6 py-4 text-center text-sm font-medium">
                            {{ $visit->vitalSign->systole }}/{{ $visit->vitalSign->diastole }} mmHg </td>
                        <td class=" px-6 py-4 text-center text-sm font-medium">
                            {{ $visit->vitalSign->height }} cm /
                            {{ number_format($visit->vitalSign->weight, 1, '.', ',') }} kg </td>
                        <td class="px-6 py-4 text-center text-sm font-medium">
                            {{ $visit->complaint }} </td>
                        <td class=" px-6 py-4 text-center text-sm font-medium capitalize">
                            {{ str($visit->internal_status)->headline() }}</td>
                        <td class="px-12 py-4 text-right text-sm font-medium">
                            <a class="{{ $visit->internal_status === 'at_practitioner' ? 'text-orange-500 hover:text-orange-700' : 'text-green-500 hover:text-green-700' }} cursor-pointer"
                                {{ $visit->status === 'finished' ? 'disabled' : '' }}
                                wire:click="startConsultation({{ $visit->id }})">{{ $visit->internal_status === 'at_practitioner' ? 'Lanjutkan Diagnosa' : 'Mulai Diagnosa' }}</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-sm font-medium text-gray-900">
                            <x-nodatafound />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="md:block hidden mt-4">
        {{ $patients->links() }}
    </div>
</div>
