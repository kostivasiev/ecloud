<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;

class Deploy extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['data' => $this->data]);

        $instance = Instance::findOrFail($this->data['instance_id']);

        if (empty($instance->image)) {
            $this->fail(new \Exception(
                'Deploy failed for ' . $instance->id . ', Failed to load image'
            ));
            return;
        }

        /** @var Response $deployResponse */
        try {
            $deployResponse = $instance->availabilityZone->kingpinService()->post(
                '/api/v2/vpc/' . $this->data['vpc_id'] . '/instance/fromtemplate',
                [
                    'json' => [
                        'templateName' => $instance->image->vm_template_name,
                        'instanceId' => $instance->getKey(),
                        'numCPU' => $instance->vcpu_cores,
                        'ramMib' => $instance->ram_capacity,
                        'resourceTierTags' => config('instance.resource_tier_tags')
                    ]
                ]
            );

            $deployResponse = json_decode($deployResponse->getBody()->getContents());
            if (!$deployResponse) {
                throw new \Exception('Deploy failed for ' . $instance->id . ', could not decode response');
            }
        } catch (RequestException $exception) {
            throw new \Exception($exception->getResponse()->getBody()->getContents());
        }

        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
