<?php

namespace App\Jobs\Network;

use App\Jobs\Job;
use App\Models\V2\Network;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

class DeleteNetworkPolicy extends Job
{
    use Batchable, LoggableModelJob;

    private Network $model;

    public function __construct(Network $network)
    {
        $this->model = $network;
    }

    public function handle()
    {
        $this->model->networkPolicy->syncDelete();
    }
}
