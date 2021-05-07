<?php

namespace App\Jobs\Nic;

use App\Jobs\Job;
use App\Models\V2\Nic;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class UnassignFloatingIP extends Job
{
    use Batchable, LoggableModelJob;
    
    private $model;

    public function __construct(Nic $nic)
    {
        $this->model = $nic;
    }

    public function handle()
    {
        $nic = $this->model;
        $logMessage = 'UnassignFloatingIp for NIC ' . $nic->id . ': ';
        Log::info($logMessage . 'Started');

        if ($this->model->sourceNat()->exists()) {
            Log::info($logMessage . 'Floating IP ' . $this->model->sourceNat->translated_id . ' unassigned');
            $this->model->sourceNat->delete();
        }
        if ($this->model->destinationNat()->exists()) {
            Log::info($logMessage . 'Floating IP ' . $this->model->sourceNat->translated_id . ' unassigned');
            $this->model->destinationNat->delete();
        }
    }
}
