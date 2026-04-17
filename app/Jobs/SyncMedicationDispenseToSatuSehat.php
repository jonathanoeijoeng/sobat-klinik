<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\OutpatientVisit;
use App\Services\SatuSehatService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncMedicationDispenseToSatuSehat implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $visit;
    public $tries = 3;

    public function __construct(OutpatientVisit $visit)
    {
        $this->visit = $visit;
    }
    public function handle(SatuSehatService $service): void
    {
        // Pastikan kita ambil data terbaru dari DB NUC
        $this->visit->refresh();

        foreach ($this->visit->prescriptions as $pres) {
            // 1. Lewati jika sudah pernah sukses dikirim
            if ($pres->satusehat_medication_dispense_id) {
                continue;
            }

            // 2. Lewati jika MedicationRequest belum ada (karena Dispense butuh Request ID)
            if (!$pres->satusehat_medication_request_id) {
                Log::error("Gagal Dispense: MedicationRequest ID belum ada untuk resep " . $pres->id);
                continue;
            }

            // 3. Panggil service (Pastikan parameter cuma satu yaitu ID, sesuai method service-mu)
            $res = $service->sendMedicationDispense($pres->id);

            if (isset($res['id'])) {
                $pres->update([
                    'satusehat_medication_dispense_id' => $res['id'],
                    'status' => 'dispensed',
                    'dispensed_at' => now(),
                ]);
            } else {
                Log::error("API SatuSehat Error untuk Resep " . $pres->id, is_array($res) ? $res : ($res->json() ?? []));
            }
        }
    }
}
