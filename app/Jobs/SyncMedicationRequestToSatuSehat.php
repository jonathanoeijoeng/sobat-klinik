<?php

namespace App\Jobs;

use App\Models\OutpatientVisit;
use App\Services\SatuSehatService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

// ... (import standar lainnya)

class SyncMedicationRequestToSatuSehat implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $visit;
    public $tries = 3;

    public function __construct(OutpatientVisit $visit)
    {
        $this->visit = $visit;
    }

    public function handle(SatuSehatService $service)
    {
        if (!$this->visit->satusehat_encounter_id) {
            return $this->release(30);
        }

        // Kita loop setiap prescription yang belum punya satusehat_id
        foreach ($this->visit->prescriptions as $pres) {
            // 1. CEK DULU: Jangan tembak API kalau record ini sudah punya ID SatuSehat
            // Ini kunci untuk menghindari error 20002 saat Job melakukan RETRY
            if ($pres->satusehat_medication_request_id) {
                continue;
            }

            $result = $service->sendMedicationRequest($pres, $this->visit);

            if (isset($result['id'])) {
                // 2. LANGSUNG UPDATE: Begitu dapat ID, simpan ke database
                $pres->update([
                    'satusehat_medication_request_id' => $result['id'],
                    'status' => 'sent_to_pharmacy', // Update status agar muncul di antrian farmasi
                ]);
            }
        }
    }
}
