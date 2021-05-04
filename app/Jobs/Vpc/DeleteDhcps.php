<?php

namespace App\Jobs\Vpc;

use App\Jobs\Job;
use App\Models\V2\Vpc;
use App\Traits\V2\JobModel;
use Illuminate\Bus\Batchable;

class DeleteDhcps extends Job
{
    use Batchable, JobModel;

    private Vpc $model;

    public function __construct(Vpc $vpc)
    {
        $this->model = $vpc;
    }

    public function handle()
    {
        $this->model->dhcps()->each(function ($dhcp) {
            $dhcp->delete();
        });
    }
}
