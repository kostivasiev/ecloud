<?php

namespace App\Jobs\Conjurer\Host;

use App\Jobs\Job;
use App\Models\V2\Host;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreateLanPolicy extends Job
{
    private $model;

    public function __construct(Host $model)
    {
        $this->model = $model;
    }

    /**
     * @return bool
     */
    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $host = $this->model;
        $vpc = $host->hostGroup->vpc;
        $availabilityZone = $host->hostGroup->availabilityZone;

        if (empty($availabilityZone->ucs_compute_name)) {
            $message = 'Failed to load UCS compute name for availability zone ' . $availabilityZone->id;
            Log::error($message);
            $this->fail(new \Exception($message));
            return false;
        }

        $createLanPolicy = false;

        try {
            // Check whether a LAN connectivity policy exists on the UCS for the VPC
            $availabilityZone->conjurerService()->get('/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $vpc->id);
        } catch (ServerException $exception) {
            $exceptionMessage = json_decode($exception->getResponse()->getBody()->getContents())->ExceptionMessage;
            if (!Str::contains($exceptionMessage, 'Cannot find LAN connectivity policy')) {
                throw $exception;
            }
            $createLanPolicy = true;
        }

        if (!$createLanPolicy) {
            Log::debug('VPC LAN Policy already exists. Nothing to do.');
            return true;
        }

        $availabilityZone->conjurerService()->post(
            '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc', [
                'json' => [
                    'vpcId' => $vpc->id,
                ],
            ]
        );

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }

    public function failed($exception)
    {
        $message = ($exception instanceof RequestException && $exception->hasResponse()) ?
            json_decode($exception->getResponse()->getBody()->getContents()) :
            $exception->getMessage();
        $this->model->setSyncFailureReason($message);
    }
}
