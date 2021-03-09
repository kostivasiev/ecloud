<?php

namespace App\Jobs\Nsx\HostGroup;

use App\Jobs\Job;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\HostGroup;
use App\Models\V2\Vpc;
use Illuminate\Support\Facades\Log;

class CreateTransportNode extends Job
{
    private $model;

    public function __construct(HostGroup $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $hostGroup = $this->model;

        $networkSwitch = $this->getNetworkSwitchDetails(
            $hostGroup->availabilityZone,
            $hostGroup->vpc
        );

        dd($networkSwitch['uuid']);

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }

    private function getNetworkSwitchDetails(AvailabilityZone $availabilityZone, Vpc $vpc) : array
    {
        $response = $availabilityZone->kingpinService()
            ->get('/api/v1/vpc/' . $vpc->id . '/network/switch');

        if (!$response || $response->getStatusCode() !== 200) {
            return false;
        }

        $json = json_decode($response->getBody()->getContents());
        if (!$json) {
            return false;
        }

        return [
            'name' => $json->name,
            'uuid' => $json->uuid,
        ];
    }

    public function failed($exception)
    {
        $this->model->setSyncFailureReason($exception->getMessage());
    }
}
