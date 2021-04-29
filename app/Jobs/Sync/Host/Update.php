<?php

namespace App\Jobs\Task\Host;

use App\Jobs\Artisan\Host\Deploy;
use App\Jobs\Conjurer\Host\CheckAvailableCompute;
use App\Jobs\Conjurer\Host\CreateAutoDeployRule;
use App\Jobs\Conjurer\Host\CreateLanPolicy;
use App\Jobs\Conjurer\Host\CreateProfile;
use App\Jobs\Conjurer\Host\PowerOn;
use App\Jobs\Job;
use App\Jobs\Kingpin\Host\CheckOnline;
use App\Models\V2\Task;
use App\Traits\V2\TaskableBatch;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class Update extends Job
{
    use TaskableBatch;

    private $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->task->id, 'resource_id' => $this->task->resource->id]);

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

        Log::info(get_class($this) . ' : Finished', ['id' => $this->task->id, 'resource_id' => $this->task->resource->id]);
    }
}
