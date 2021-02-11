<?php

namespace App\Jobs\Nsx\NetworkAclRule;

use App\Jobs\Job;
use App\Models\V2\NetworkAclRule;
use Illuminate\Support\Facades\Log;

class Deploy extends Job
{
    private $model;

    public function __construct(NetworkAclRule $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        // @todo Deploy implementation goes here

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}
