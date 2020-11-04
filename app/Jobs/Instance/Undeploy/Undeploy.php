<?php

namespace App\Jobs\Instance\Undeploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
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

    public function handle()
    {
        Log::info('Performing Undeploy for instance ' . $this->data['instance_id']);
        $instance = Instance::withTrashed()->findOrFail($this->data['instance_id']);
        $logMessage = 'Undeploy for instance ' . $instance->getKey() . ': ';
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
        } catch (GuzzleException $exception) {
            // Catch already deleted
            if ($exception->hasResponse()
                && json_decode($exception->getResponse()->getBody()->getContents())->ExceptionType == 'UKFast.VimLibrary.Exception.EntityNotFoundException') {
                Log::info('Attempted to undeploy instance, but entity was not found, skipping.');
            } else {
                $this->fail($exception);
                return;
            }
        }

        Log::info($logMessage . 'Success');
    }
}
