<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Psr7\Response;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class RunImageReadinessScript extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    public $tries = 120;

    public $backoff = 30;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    public function handle()
    {
        $instance = $this->model;

        if (empty($instance->image->readiness_script)) {
            Log::info('No readiness script for ' . $instance->id . ', skipping');
            return;
        }

        $guestAdminCredential = $instance->credentials()
            ->where('username', 'root')
            ->firstOrFail();
        if (!$guestAdminCredential) {
            $message = 'RunApplianceBootstrap failed for ' . $instance->id . ', no admin credentials found';
            Log::error($message);
            $this->fail(new \Exception($message));
            return;
        }

        $response = $instance->availabilityZone->kingpinService()->post(
            '/api/v2/vpc/' . $this->model->vpc->id . '/instance/' . $this->model->id . '/guest/linux/script',
            [
                'json' => [
                    'encodedScript' => base64_encode($this->model->image->readiness_script),
                    'username' => $guestAdminCredential->username,
                    'password' => $guestAdminCredential->password,
                ],
            ]
        );

        $response = json_decode($response->getBody()->getContents());
        if (!$response) {
            throw new \Exception('Could not decode response from readiness script for ' . $instance->id);
        }

        // 0 = completed, 1 = error, any other = retry
        switch ($response->exitCode) {
            case 0:
                Log::info(get_class($this) . ': Readiness script for instance ' .  $instance->id . ' completed successfully', ['id' => $instance->id]);
                return;
            case 1:
                Log::error(get_class($this) . ': Readiness script for instance ' .  $instance->id . ' failed', ['id' => $instance->id]);
                $this->fail(new \Exception('Readiness script for ' . $instance->id . ' failed with exit code 1. Output: ' . $response->output));
                return;
            default:
                Log::info(get_class($this) . ': Readiness script not yet complete, retrying in ' . $this->backoff . ' seconds');
                $this->release($this->backoff);
        }
    }
}
