<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use GuzzleHttp\Psr7\Response;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class Deploy extends Job
{
    use Batchable;
    
    private $instance;

    public function __construct(Instance $instance)
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

        $rand = bin2hex(random_bytes(4));

        Log::info("DEBUG :: Testing instance deployment", ['rand'=>$rand, 'attempt'=>$this->attempts()]);

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
        } catch (\Exception $exception) {
            Log::info("DEBUG :: Failed instance deployment", ['rand'=>$rand, 'attempt'=>$this->attempts()]);
            throw $exception;
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->instance->id]);
    }
}
