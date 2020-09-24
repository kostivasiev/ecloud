<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Vpc;
use App\Services\V2\KingpinService;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;

class WaitOsCustomisation extends Job
{
    const RETRY_ATTEMPTS = 360; // Retry every 5 seconds for 20 minutes

    const RETRY_DELAY = 5;

    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/329
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
            if ($response->getStatusCode() != 200) {
                $this->fail(new \Exception(
                    'WaitOsCustomisation failed for '.$instance->id.', Kingpin status was '.$response->getStatusCode()
                ));
                return;
            }

            $data = json_decode($response->getBody()->getContents());
            if (!$data) {
                $this->fail(new \Exception('WaitOsCustomisation failed for '.$instance->id.', could not decode response'));
                return;
            }

            if ($data->status !== 'Succeeded') {
                if ($this->attempts() <= static::RETRY_ATTEMPTS) {
                    $this->release(static::RETRY_DELAY);
                    Log::info(
                        'Check for WaitOsCustomisation for '.$instance->id.' returned "'.
                        $data->status.'", retrying in '.static::RETRY_DELAY.' seconds'
                    );
                    return;
                } else {
                    $this->fail(new \Exception('Timed out on WaitOsCustomisation for '.$instance->id));
                    return;
                }
            }
        } catch (GuzzleException $exception) {
            $this->fail(new \Exception(
                'WaitOsCustomisation failed for '.$instance->id.' : '.
                $exception->getResponse()->getBody()->getContents()
            ));
            return;
        }
    }
}
