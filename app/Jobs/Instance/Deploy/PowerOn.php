<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
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
        $instance = Instance::find($this->data['instance_id']);

        Log::info('Attempted to PowerOn '.$instance->id);

        $kingpinService = app()->make(KingpinService::class, $instance->availabilityZone);
        try {
            /** @var Response $response */
            $response = $kingpinService->post(
                '/api/v2/vpc/'.$this->data['vpc_id'].'/instance/'.$instance->id.'/power'
            );
            if ($response->getStatusCode() == 200) {
                Log::info('PowerOn job fin'.$instance->id);
                return;
            }
            $message = 'Failed to PowerOn '.$instance->id.' with : '.$response->getReasonPhrase();
            Log::error($message);
            $this->fail(new \Exception($message));
        } catch (GuzzleException $exception) {
            $message = 'PowerOn job for instance '.$this->data['vpc_id'].' failed with : '.
                $exception->getResponse()->getBody()->getContents();
            Log::error($message);
            $this->fail(new \Exception($message));
            return;
        }
    }
}
