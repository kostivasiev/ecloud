<?php

namespace App\Jobs\Sync;

use App\Jobs\Job;
use App\Traits\V2\JobModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Delete extends Job
{
    use JobModel;

    private $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        $this->model->syncDelete();
    }
}
