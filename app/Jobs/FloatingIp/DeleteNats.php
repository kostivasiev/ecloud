<?php

namespace App\Jobs\FloatingIp;

use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use App\Traits\V2\JobModel;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class DeleteNats extends Job
{
    use Batchable, JobModel;

    private $model;

    public function __construct(FloatingIp $floatingIp)
    {
        $this->model = $floatingIp;
    }

    public function handle()
    {
        if ($this->model->sourceNat()->exists()) {
            $this->model->sourceNat->delete();
        }
        if ($this->model->destinationNat()->exists()) {
            $this->model->destinationNat->delete();
        }
    }
}
