<?php

namespace App\Jobs\Instance;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Vpc;
use App\Services\V2\KingpinService;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;

class PowerOn extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/328
     */
    public function handle()
    {
        Log::info('Attempting to PowerOn instance '.$this->data['instance_id']);
        $instance = Instance::findOrFail($this->data['instance_id']);
        $vpc = Vpc::findOrFail($this->data['vpc_id']);
        $kingpinService = app()->make(KingpinService::class, $instance->availabilityZone);
        try {
            /** @var Response $response */
            $response = $kingpinService->post('/api/v2/vpc/'.$vpc->id.'/instance/'.$instance->id.'/power');
            if ($response->getStatusCode() == 200) {
                Log::info('PowerOn finished successfully for instance '.$instance->id);
                return;
            }
            $this->fail(new \Exception(
                'Failed to PowerOn '.$instance->id.', Kingpin status was '.$response->getStatusCode()
            ));
            return;
        } catch (GuzzleException $exception) {
            $this->fail(new \Exception(
                'Failed to PowerOn '.$instance->id.' : '.$exception->getResponse()->getBody()->getContents()
            ));
            return;
        }
    }
}
