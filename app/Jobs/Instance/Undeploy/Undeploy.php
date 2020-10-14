<?php

namespace App\Jobs\Instance;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Vpc;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;

class Undeploy extends Job
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
        Log::info('Performing Undeploy for instance ' . $this->data['instance_id']);
        $instance = Instance::findOrFail($this->data['instance_id']);
        try {
            /** @var Response $response */
            $response = $instance->availabilityZone
                ->kingpinService()
                ->delete('/api/v2/vpc/' . $instance->vpc_id . '/instance/' . $instance->getKey());
            if ($response->getStatusCode() != 200) {
                $this->fail(new \Exception(
                    $logMessage . 'Failed, Kingpin status was ' . $response->getStatusCode()
                ));
                return;
            }
            Log::info($logMessage . 'Success');
            return;
        } catch (GuzzleException $exception) {
            $this->fail($exception);
            return;
        }
    }
}
