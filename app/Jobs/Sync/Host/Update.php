<?php

namespace App\Jobs\Sync\Host;

use App\Jobs\Artisan\Host\Deploy;
use App\Jobs\Conjurer\Host\CheckAvailableCompute;
use App\Jobs\Conjurer\Host\CreateAutoDeployRule;
use App\Jobs\Conjurer\Host\CreateLanPolicy;
use App\Jobs\Conjurer\Host\CreateProfile;
use App\Jobs\Conjurer\Host\PowerOn;
use App\Jobs\Job;
use App\Jobs\Kingpin\Host\CheckOnline;
use App\Models\V2\Task;
use App\Traits\V2\LoggableModelJob;
use App\Traits\V2\TaskableBatch;
use GuzzleHttp\Exception\RequestException;

class Update extends Job
{
    use TaskableBatch, LoggableModelJob;

    private $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        $host = $this->task->resource;
        $vpc = $host->hostGroup->vpc;
        $availabilityZone = $host->hostGroup->availabilityZone;

        $deployed = true;
        // Only create if the host doesnt already exist
        try {
            $availabilityZone->conjurerService()->get(
                '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $vpc->id . '/host/' . $host->id
            );
        } catch (RequestException $exception) {
            if ($exception->getCode() == 404) {
                $deployed = false;
            } else {
                throw $exception;
            }
        }

        if (!$deployed) {
            $this->updateTaskBatch([
                [
                    new CreateLanPolicy($host),
                    new CheckAvailableCompute($host),
                    new CreateProfile($host),
                    new CreateAutoDeployRule($host),
                    new Deploy($host),
                    new PowerOn($host),
                    new CheckOnline($host),
                ],
            ])->dispatch();
        } else {
            $this->task->completed = true;
            $this->task->save();
        }
    }
}
