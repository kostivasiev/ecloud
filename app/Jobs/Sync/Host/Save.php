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
use App\Models\V2\Host;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class Save extends Job
{
    private $model;

    public function __construct(Host $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $host = $this->model;
        $vpc = $host->hostGroup->vpc;
        $availabilityZone = $host->hostGroup->availabilityZone;

        $jobs = [];

        // Only create if the host doesnt already exist
//        try {
//            $availabilityZone->conjurerService()->get(
//                '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $vpc->id . '/host/' . $host->id
//            );
//        } catch (RequestException $exception) {
//            if ($exception->getCode() == 404) {
//                $jobs = [
//                    new CreateLanPolicy($this->model),
//                    new CheckAvailableCompute($this->model),
//                    new CreateProfile($this->model),
//                    new CreateAutoDeployRule($this->model),
//                    new Deploy($this->model),
//
//                    new PowerOn($this->model),
//                    new CheckOnline($this->model),
//                ];
//            }
//        }

        $jobs[] = new \App\Jobs\Sync\Completed($this->model);

        dispatch(array_shift($jobs)->chain($jobs));

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }

    public function failed($exception)
    {
        $message = ($exception instanceof RequestException && $exception->hasResponse()) ?
            $exception->getResponse()->getBody()->getContents() :
            $exception->getMessage();

        $this->model->setSyncFailureReason($message);
    }
}
