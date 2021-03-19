<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class Deploy extends Job
{
    use Batchable;
    
    private $instance;

    public function __construct($instance)
    {
        $this->instance = $instance;
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->instance->id]);

        if (empty($this->instance->image)) {
            $this->fail(new \Exception(
                'Deploy failed for ' . $this->instance->id . ', Failed to load image'
            ));
            return;
        }

        /** @var Response $deployResponse */
        try {
            $deployResponse = $this->instance->availabilityZone->kingpinService()->post(
                '/api/v2/vpc/' . $this->instance->vpc->id . '/instance/fromtemplate',
                [
                    'json' => [
                        'templateName' => $this->instance->image->vm_template_name,
                        'instanceId' => $this->instance->getKey(),
                        'numCPU' => $this->instance->vcpu_cores,
                        'ramMib' => $this->instance->ram_capacity,
                        'resourceTierTags' => config('instance.resource_tier_tags')
                    ]
                ]
            );

            $deployResponse = json_decode($deployResponse->getBody()->getContents());
            if (!$deployResponse) {
                throw new \Exception('Deploy failed for ' . $this->instance->id . ', could not decode response');
            }
        } catch (RequestException $exception) {
            throw new \Exception($exception->getResponse()->getBody()->getContents());
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->instance->id]);
    }
}
