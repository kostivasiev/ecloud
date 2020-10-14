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
        $logMessage = 'ConfigureWinRm for ' . $instance->id . ': ';
        if ($instance->platform != 'Windows') {
            Log::info($logMessage . 'Platform is not Windows. Skipped.');
            return;
        }

        $guestAdminCredential = $instance->credentials()
            ->where('username', ($instance->platform == 'Linux') ? 'root' : 'graphite.rack')
            ->firstOrFail();
        if (!$guestAdminCredential) {
            $message = 'ConfigureWinRm failed for ' . $instance->id . ', no admin credentials found';
            Log::error($message);
            $this->fail(new \Exception($message));
            return;
        }

        try {
            /** @var Response $response */
            $response = $instance->availabilityZone->kingpinService()->post(
                '/api/v2/vpc/' . $instance->vpc->id . '/instance/' . $instance->id . '/guest/windows/winrm',
                [
                    'json' => [
                        'username' => 'administrator',
                        'password' => $guestAdminCredential->password,
                    ],
                ]
            );

            if ($response->getStatusCode() != 200) {
                $message = 'Failed ConfigureWinRm for ' . $instance->id;
                Log::error($message, ['response' => $response]);
                $this->fail(new \Exception($message));
                return;
            }
        } catch (GuzzleException $exception) {
            $message = 'Failed ConfigureWinRm for ' . $instance->id;
            Log::error($message, ['exception' => $exception]);
            $this->fail(new \Exception($message));
            return;
        }
    }
}
