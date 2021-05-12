<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Psr7\Response;
use Illuminate\Bus\Batchable;

class Deploy extends Job
{
    use Batchable, LoggableModelJob;
    
    private $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        if (empty($this->model->image)) {
            $this->fail(new \Exception(
                'Deploy failed for ' . $this->model->id . ', Failed to load image'
            ));
            return;
        }

        $deployData = [
            'templateName' => $this->model->image->vm_template_name,
            'instanceId' => $this->model->getKey(),
            'numCPU' => $this->model->vcpu_cores,
            'ramMib' => $this->model->ram_capacity,
            'resourceTierTags' => config('instance.resource_tier_tags'),
            'backupEnabled' => $this->model->backup_enabled,
        ];

        if (!empty($this->model->host_group_id)) {
            unset($deployData['resourceTierTags']);
            $deployData['hostGroupId'] = $this->model->host_group_id;
        }

        /** @var Response $deployResponse */
        $deployResponse = $this->model->availabilityZone->kingpinService()->post(
            '/api/v2/vpc/' . $this->model->vpc->id . '/instance/fromtemplate',
            [
                'json' => $deployData
            ]
        );

        $deployResponse = json_decode($deployResponse->getBody()->getContents());
        if (!$deployResponse) {
            throw new \Exception('Deploy failed for ' . $this->model->id . ', could not decode response');
        }
    }
}
