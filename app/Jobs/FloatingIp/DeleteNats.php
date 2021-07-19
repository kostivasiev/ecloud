<?php

namespace App\Jobs\FloatingIp;

use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

class DeleteNats extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    public function __construct(FloatingIp $floatingIp)
    {
        $this->model = $floatingIp;
    }

    public function handle()
    {
        if ($this->model->sourceNat()->exists()) {
            $this->model->sourceNat->syncDelete();
        }
        if ($this->model->destinationNat()->exists()) {
            $this->model->destinationNat->syncDelete();
        }
    }
}
