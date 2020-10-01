<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Vpc;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;

class RunBootstrapScript extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/334
     */
    public function handle()
    {
        Log::info('Starting RunBootstrapScript for instance '.$this->data['instance_id']);
        $instance = Instance::findOrFail($this->data['instance_id']);
        $vpc = Vpc::findOrFail($this->data['vpc_id']);
        $credential = $instance->credentials()
            ->where('user', ($instance->platform == 'Linux') ? 'root' : 'administrator')
            ->firstOrFail();
        if (!$credential) {
            $this->fail(new \Exception('RunBootstrapScript failed for '.$instance->id.', no credentials found'));
            return;
        }

        $endpoint = ($instance->platform == 'Linux') ? 'linux/script' : 'windows/script';
        try {
            /** @var Response $response */
            $response = $instance->availabilityZone->kingpinService()->post('/api/v2/vpc/'.$vpc->id.'/instance/'.$instance->id.'/guest/'.$endpoint, [
                'json' => [
                    'encodedScript' => base64_encode(
                        (new \Mustache_Engine())->loadTemplate($instance->applianceVersion->script_template)
                            ->render($this->data['appliance_data'])
                    ),
                    'username' => $credential->user,
                    'password' => $credential->password,
                ],
            ]);
            if ($response->getStatusCode() == 200) {
                Log::info('RunBootstrapScript finished successfully for instance '.$instance->id);
                return;
            }
            $this->fail(new \Exception(
                'Failed RunBootstrapScript for '.$instance->id.', Kingpin status was '.$response->getStatusCode()
            ));
            return;
        } catch (GuzzleException $exception) {
            $this->fail(new \Exception(
                'Failed RunBootstrapScript for '.$instance->id.' : '.$exception->getResponse()->getBody()->getContents()
            ));
            return;
        }
    }
}
