<?php

namespace App\Jobs\Sync;

use App\Jobs\Job;
use App\Traits\V2\JobModel;
use Illuminate\Bus\Batchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Completed extends Job
{

    use Batchable, JobModel;

    private $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        $this->model->setSyncCompleted();
    }
}
