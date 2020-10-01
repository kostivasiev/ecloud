<?php

namespace App\Jobs\Instance;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Vpc;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;

class PowerOff extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        Log::info('Attempting to PowerOn instance '.$this->data['instance_id']);
        $instance = Instance::findOrFail($this->data['instance_id']);
        $vpc = Vpc::findOrFail($this->data['vpc_id']);
        try {
            /** @var Response $response */
            $response = $instance->availabilityZone->kingpinService()->delete(
                '/api/v2/vpc/'.$vpc->id.'/instance/'.$instance->id.'/power'
            );
            if ($response->getStatusCode() == 200) {
                Log::info('PowerOff finished successfully for instance '.$instance->id);
                return;
            }
            $this->fail(new \Exception(
                'Failed to PowerOff '.$instance->id.', Kingpin status was '.$response->getStatusCode()
            ));
            return;
        } catch (GuzzleException $exception) {
            $this->fail(new \Exception(
                'Failed to PowerOff '.$instance->id.' : '.$exception->getResponse()->getBody()->getContents()
            ));
            return;
        }
    }
}
