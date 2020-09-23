<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Vpc;
use App\Services\V2\KingpinService;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;

class WaitOsCustomisation extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @see Missing Issue?
     */
    public function handle()
    {
        Log::info('Performing WaitOsCustomisation for instance '.$this->data['instance_id']);
        $instance = Instance::findOrFail($this->data['instance_id']);
        $vpc = Vpc::findOrFail($this->data['vpc_id']);
        $kingpinService = app()->make(KingpinService::class, $instance->availabilityZone);
        try {
            /** @var Response $response */
            $response = $kingpinService->post('/api/v2/vpc/'.$vpc->id.'/instance/'.$instance->id.'/oscustomization/status');
            if ($response->getStatusCode() == 200) {
                Log::info('WaitOsCustomisation finished successfully for instance '.$instance->id);
                return;
            }
            $this->fail(new \Exception(
                'Failed to WaitOsCustomisation '.$instance->id.', Kingpin status was '.$response->getStatusCode()
            ));
            return;
        } catch (GuzzleException $exception) {
            $this->fail(new \Exception(
                'Failed to WaitOsCustomisation '.$instance->id.' : '.$exception->getResponse()->getBody()->getContents()
            ));
            return;
        }
    }
}
