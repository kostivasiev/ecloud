<?php

namespace App\Jobs\Sync\Host;

use App\Jobs\Artisan\Host\Deploy as ArtisanHostDeploy;
use App\Jobs\Conjurer\Host\CreateAutoDeployRule;
use App\Jobs\Conjurer\Host\CreateLanPolicy;
use App\Jobs\Conjurer\Host\CreateProfile;
use App\Jobs\Conjurer\Host\PowerOn;
use App\Jobs\Job;
use App\Jobs\Kingpin\Host\CheckOnline;
use App\Jobs\Kingpin\Host\CheckProfileApplied;
use App\Models\V2\Task;
use App\Traits\V2\LoggableTaskJob;
use App\Traits\V2\TaskableBatch;
use GuzzleHttp\Exception\RequestException;

class Update extends Job
{
    use TaskableBatch, LoggableTaskJob;

    private $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        $host = $this->task->resource;
        $this->updateTaskBatch([
            [
                new CreateLanPolicy($host),
                new CreateProfile($host),
                new CreateAutoDeployRule($host),
                new ArtisanHostDeploy($host),
                new PowerOn($host),
                new CheckOnline($host),
            ],
        ])->dispatch();
    }
}
