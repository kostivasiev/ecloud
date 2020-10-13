<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Vpc;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;

class RunApplianceBootstrap extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/333
     */
    public function handle()
    {
        Log::info('Starting RunApplianceBootstrap for instance ' . $this->data['instance_id']);
        $instance = Instance::findOrFail($this->data['instance_id']);
        $vpc = Vpc::findOrFail($this->data['vpc_id']);
        $credential = $instance->credentials()
            ->where('username', ($instance->platform == 'Linux') ? 'root' : 'administrator')
            ->firstOrFail();
        if (!$credential) {
            $this->fail(new \Exception('RunApplianceBootstrap failed for ' . $instance->id . ', no credentials found'));
            return;
        }

        if ($instance->platform !== 'Linux') {
            Log::info('RunApplianceBootstrap for ' . $instance->id . ', nothing to do for non-Linux platforms');
            return;
        }

        try {
            /** @var Response $response */
            $response = $instance->availabilityZone->kingpinService()->post(
                '/api/v2/vpc/' . $vpc->id . '/instance/' . $instance->id . '/guest/linux/script',
                [
                    'json' => [
                        'encodedScript' => base64_encode(
                            (new \Mustache_Engine())->loadTemplate($instance->applianceVersion->script_template)
                                ->render(json_decode($this->data['appliance_data']))
                        ),
                        'username' => $credential->username,
                        'password' => $credential->password,
                    ],
                ]
            );
            if ($response->getStatusCode() == 200) {
                Log::info('RunApplianceBootstrap finished successfully for instance ' . $instance->id);
                return;
            }
            $this->fail(new \Exception(
                'Failed RunApplianceBootstrap for ' . $instance->id . ', Kingpin status was ' . $response->getStatusCode()
            ));
            return;
        } catch (GuzzleException $exception) {
            $this->fail(new \Exception(
                'Failed RunApplianceBootstrap for ' . $instance->id . ' : ' . $exception->getResponse()->getBody()->getContents()
            ));
            return;
        }
    }
}
