<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use App\Services\SatuSehatService;
use App\Models\OutpatientVisit;

class SyncObservationVitalSign implements ShouldQueue
{
    use Queueable;

    public $visit;

    public function __construct(OutpatientVisit $visit)
    {
        $this->visit = $visit;
    }
    /**
     * Create a new job instance.
     */
    /**
     * Execute the job.
     */
    public function handle(SatuSehatService $service)
    {
        // 1. REFRESH data visit agar mendapatkan satusehat_encounter_id terbaru
        $visit = $this->visit->fresh(['vitalSign', 'patient', 'practitioner']);
        $vs = $visit->vitalSign;

        if (!$visit->satusehat_encounter_id) {
            Log::error("BP & Temp Gagal: satusehat_encounter_id masih NULL di database saat Job jalan.");
            return;
        }

        $updateData = [];

        // 2. Eksekusi BP
        $resBp = $service->createObservationBloodPressure($visit);
        if ($resBp && $resBp->successful()) {
            $updateData['ss_bp_id'] = $resBp->json('id');
        } else {
            Log::error("BP Gagal di SS: " . ($resBp ? $resBp->body() : 'Null Response'));
        }

        // 3. Eksekusi Weight & Height (Yang sudah lancar)
        if ($vs->weight) {
            $resW = $service->createSimpleObservation($visit, '29463-7', 'Body weight', $vs->weight, 'kg', 'kg');
            if ($resW && $resW->successful()) $updateData['ss_weight_id'] = $resW->json('id');
        }

        if ($vs->height) {
            $resH = $service->createSimpleObservation($visit, '8302-2', 'Body height', $vs->height, 'cm', 'cm');
            if ($resH && $resH->successful()) $updateData['ss_height_id'] = $resH->json('id');
        }

        // 4. Eksekusi Temperature (Gunakan nama kolom baru Anda)
        if ($vs->temperature) {
            $resT = $service->createSimpleObservation($visit, '8310-5', 'Body temperature', $vs->temp, 'Cel', 'Cel');
            if ($resT && $resT->successful()) {
                $updateData['ss_temperature_id'] = $resT->json('id');
            } else {
                Log::error("Temp Gagal di SS: " . ($resT ? $resT->body() : 'Null Response'));
            }
        }

        // 5. Update Database Sekaligus
        if (!empty($updateData)) {
            \App\Models\VitalSign::where('id', $vs->id)->update($updateData);
            Log::info("Sync Vital Signs Selesai untuk Visit {$visit->id}", $updateData);
        }
    }
}
