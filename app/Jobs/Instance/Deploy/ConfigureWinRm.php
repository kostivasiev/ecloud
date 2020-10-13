<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;

class ConfigureWinRm extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        Log::info('Starting ConfigureWinRm for instance ' . $this->data['instance_id']);
        $instance = Instance::findOrFail($this->data['instance_id']);
        $logMessage = 'ConfigureWinRm for ' . $instance->getKey() . ': ';
        if ($instance->platform != 'Windows') {
            Log::info($logMessage . 'Platform is not Windows. Skipped.');
            return;
        }

        $credential = $instance->credentials()->where('username', 'administrator')->firstOrFail();
        if (!$credential) {
            $this->fail(new \Exception($logMessage . 'Failed. No credentials found'));
            return;
        }

        try {
            /** @var Response $response */
            $response = $instance->availabilityZone->kingpinService()->post(
                '/api/v2/vpc/' . $instance->vpc->getKey() . '/instance/' . $instance->getKey() . '/guest/windows/winrm',
                [
                    'json' => [
                        'username' => $credential->username,
                        'password' => $credential->password,
                    ],
                ]
            );
            if ($response->getStatusCode() == 200) {
                Log::info($logMessage . 'Success');
                return;
            }
            $this->fail(new \Exception($logMessage . 'Failed. Kingpin status was ' . $response->getStatusCode()));
            return;
        } catch (GuzzleException $exception) {
            $this->fail(new \Exception($logMessage . 'Failed. ' . $exception->getResponse()->getBody()->getContents()));
            return;
        }
    }
}
