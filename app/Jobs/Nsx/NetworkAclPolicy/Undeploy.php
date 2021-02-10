<?php

namespace App\Jobs\Nsx\NetworkAclPolicy;

use App\Jobs\Job;
use App\Models\V2\NetworkAclPolicy;
use Illuminate\Support\Facades\Log;

class Undeploy extends Job
{
    const RETRY_DELAY = 5;

    public $tries = 500;

    private $model;

    public function __construct(NetworkAclPolicy $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        // @todo NSX undeploy to be added here

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}
