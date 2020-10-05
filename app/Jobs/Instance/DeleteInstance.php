<?php

namespace App\Jobs\Instance;

use App\Jobs\Job;
use App\Models\V2\Instance;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;

class DeleteInstance extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/445
     */
    public function handle()
    {
        Log::info('Attempting to Delete instance ' . $this->data['instance_id']);
        $instance = Instance::findOrFail($this->data['instance_id']);
        try {
            /** @var Response $response */
            $response = $instance->availabilityZone
                ->kingpinService()
                ->delete('/api/v2/vpc/'.$instance->vpc_id.'/instance/'.$instance->getKey());
            if ($response->getStatusCode() == 200) {
                Log::info('Delete finished successfully for instance ' . $instance->id);
                return;
            }
            $this->fail(new \Exception(
                'Failed to Delete ' . $instance->id . ', Kingpin status was ' . $response->getStatusCode()
            ));
            return;
        } catch (GuzzleException $exception) {
            $this->fail(new \Exception(
                'Failed to Delete ' . $instance->id . ' : ' . $exception->getResponse()->getBody()->getContents()
            ));
            return;
        }
    }
}
