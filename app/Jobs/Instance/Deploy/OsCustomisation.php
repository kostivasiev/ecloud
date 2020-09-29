<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Vpc;
use App\Services\V2\KingpinService;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;
use UKFast\Admin\Devices\AdminClient;
use GuzzleHttp\Exception\GuzzleException;

class OsCustomisation extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/331
     */
    public function handle()
    {
        Log::info('Starting OsCustomisation for instance '.$this->data['instance_id']);
        $instance = Instance::findOrFail($this->data['instance_id']);
        $vpc = Vpc::findOrFail($this->data['vpc_id']);
        $credential = $instance->credentials()
            ->where('user', ($instance->platform == 'Linux') ? 'root' : 'administrator')
            ->firstOrFail();
        if (!$credential) {
            $this->fail(new \Exception('OsCustomisation failed for '.$instance->id.', no credentials found'));
            return;
        }

        try {
            $kingpinService = app()->make(KingpinService::class, [$instance->availabilityZone]);
            /** @var Response $response */
            $response = $kingpinService->put('/api/v2/vpc/'.$vpc->id.'/instance/'.$instance->id.'/oscustomization', [
                'json' => [
                    'platform' => $instance->platform,
                    'password' => $credential->password,
                    'hostname' => $instance->id,
                ],
            ]);
            if ($response->getStatusCode() == 200) {
                Log::info('OsCustomisation finished successfully for instance '.$instance->id);
                return;
            }
            $this->fail(new \Exception(
                'Failed OsCustomisation for '.$instance->id.', Kingpin status was '.$response->getStatusCode()
            ));
            return;
        } catch (GuzzleException $exception) {
            $this->fail(new \Exception(
                'Failed OsCustomisation for '.$instance->id.' : '.$exception->getResponse()->getBody()->getContents()
            ));
            return;
        }
    }
}
