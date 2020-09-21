<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
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
        Log::info('Attempted to PowerOn '.$this->data['instance_id']);

        $kingpinService = app()->make(KingpinService::class);
        try {
            /** @var Response $response */
            $response = $kingpinService->post(
                '/api/v2/vpc/'.$this->data['vpc_id'].'/instance/'.$this->data['instance_id'].'/power'
            );
            if ($response->getStatusCode() == 200) {
                Log::info('PowerOn job fin'.$this->data['instance_id']);
                return;
            }
            $message = 'Failed to PowerOn '.$this->data['instance_id'].' with : ' .
                $response->getReasonPhrase();
            Log::error($message);
            $this->fail(new \Exception($message));
        } catch (GuzzleException $exception) {
            $message = 'PowerOn job for instance '.$this->data['vpc_id'].' failed with : ' .
                $exception->getResponse()->getBody()->getContents();
            Log::error($message);
            $this->fail(new \Exception($message));
            return;
        }
    }
}
