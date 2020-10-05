<?php

namespace App\Jobs\Instance;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Vpc;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;

class GuestRestart extends Job
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
        Log::info('Attempting to GuestRestart instance ' . $this->data['instance_id']);
        $instance = Instance::findOrFail($this->data['instance_id']);
        $vpc = Vpc::findOrFail($this->data['vpc_id']);
        try {
            /** @var Response $response */
            $response = $instance->availabilityZone->kingpinService()->put(
                '/api/v2/vpc/' . $vpc->id . '/instance/' . $instance->id . '/power/guest/restart'
            );
            if ($response->getStatusCode() == 200) {
                Log::info('GuestRestart finished successfully for instance ' . $instance->id);
                return;
            }
            $this->fail(new \Exception(
                'Failed to GuestRestart ' . $instance->id . ', Kingpin status was ' . $response->getStatusCode()
            ));
            return;
        } catch (GuzzleException $exception) {
            $this->fail(new \Exception(
                'Failed to GuestRestart ' . $instance->id . ' : ' . $exception->getResponse()->getBody()->getContents()
            ));
            return;
        }
    }
}
