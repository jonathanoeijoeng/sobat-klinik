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
        // handle() di dalam Job
        foreach ($this->visit->prescriptions as $pres) {
            if ($pres->satusehat_medication_dispense_id) continue;

            $res = $service->sendMedicationDispense($pres, $this->visit);
            if (isset($res['id'])) {
                $pres->update([
                    'satusehat_medication_dispense_id' => $res['id'],
                    'status' => 'dispensed',
                    'handed_over_at' => now(),
                ]);
            }
        }
    }
}
