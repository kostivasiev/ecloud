<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Models\V2\Nic;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;
use IPLib\Range\Subnet;

class ConfigureNics extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    public function handle()
    {
        $network = Network::findOrFail($this->model->deploy_data['network_id']);
        $getInstanceResponse = $this->model->availabilityZone->kingpinService()->get(
            '/api/v2/vpc/' . $this->model->vpc->id . '/instance/' . $this->model->id
        );

        $instanceData = json_decode($getInstanceResponse->getBody()->getContents());
        if (!$instanceData) {
            throw new \Exception('Deploy failed for ' . $this->model->id . ', could not decode response');
        }

        Log::info(get_class($this) . ' : ' . count($instanceData->nics) . ' NIC\'s found');

        foreach ($instanceData->nics as $nicData) {
            $nic = app()->make(Nic::class);
            $nic->mac_address = $nicData->macAddress;
            $nic->instance_id = $this->model->id;
            $nic->network_id = $network->id;
            $nic->syncSave();
            Log::info(get_class($this) . ' : Created NIC resource ' . $nic->id);
        }
    }
}
