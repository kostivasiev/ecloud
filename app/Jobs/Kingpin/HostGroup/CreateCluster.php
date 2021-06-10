<?php

namespace App\Jobs\Kingpin\HostGroup;

use App\Jobs\Job;
use App\Models\V2\HostGroup;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateCluster extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    public function __construct(HostGroup $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        $hostGroup = $this->model;

        // Check if it already exists and if do skip creating it
        try {
            $response = $hostGroup->availabilityZone->kingpinService()
                ->get('/api/v2/vpc/' . $hostGroup->vpc->id . '/hostgroup/' . $hostGroup->id);
            if ($response->getStatusCode() == 200) {
                Log::debug(get_class($this) . ' : HostGroup already exists, nothing to do.', ['id' => $this->model->id]);
                return true;
            }
        } catch (RequestException $exception) {
            if ($exception->getCode() != 404) {
                throw $exception;
            }
        }

        $hostGroup->availabilityZone->kingpinService()->post(
            '/api/v2/vpc/' . $hostGroup->vpc->id . '/hostgroup',
            [
                'json' => [
                    'hostGroupId' => $hostGroup->id,
                    'shared' => false,
                ],
            ]
        );
    }
}
