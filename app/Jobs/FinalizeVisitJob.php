<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\SatuSehatService;
use App\Models\OutpatientVisit;
use App\Models\Invoice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FinalizeVisitJob implements ShouldQueue
{
    use Queueable;

    public $visit;
    public $tries = 3;
    public $backoff = 60;


    /**
     * Create a new job instance.
     */
    public function __construct($visit)
    {
        $this->visit = $visit;
    }

    /**
     * Execute the job.
     */
    public function handle(SatuSehatService $service)
    {
        // 1. Update ke SatuSehat
        $resEncounter = $service->updateEncounterStatusAndDiagnosis($this->visit, 'finished');

        if (isset($resEncounter['id'])) {
            DB::transaction(function () {
                // Logika kalkulasi invoice kamu pindah ke sini
                $this->visit->load('prescriptions.medicine');

                $totalMedicineFee = $this->visit->prescriptions->reduce(function ($carry, $p) {
                    return $carry + (($p->medicine->het_price ?? 0) * ($p->qty_ordered ?? 0));
                }, 0);

                $this->visit->update([
                    'status' => 'finished',
                    'finished_at' => now(),
                ]);

                // $invoice = Invoice::where('visit_id', $this->visit->id)->first();
                // if ($invoice) {
                //     $doctorFee = $this->visit->practitioner->fee ?? 0;
                //     $invoice->update([
                //         'doctor_fee' => $doctorFee,
                //         'medicine_total' => $totalMedicineFee,
                //         'grand_total' => $doctorFee + $totalMedicineFee + $invoice->registration_fee,
                //     ]);
                // }
            });

            Log::info("Kunjungan {$this->visit->id} berhasil difinalisasi.");
        }
    }
}
