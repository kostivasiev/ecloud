<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Traits\V2\Jobs\RunsScripts;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Psr7\Response;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class RunImageReadinessScript extends Job
{
    use Batchable, LoggableModelJob, RunsScripts;

    private $model;

    public $tries = 120;

    public $backoff = 30;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    public function handle()
    {
        $instance = $this->model;

        if (empty($instance->image->readiness_script)) {
            Log::info('No readiness script for ' . $instance->id . ', skipping');
            return;
        }

        $this->runScript($instance, $instance->image->readiness_script);
    }
}
