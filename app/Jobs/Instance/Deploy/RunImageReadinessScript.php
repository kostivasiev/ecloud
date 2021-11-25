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
            ->where('username', ($instance->platform == 'Linux') ? 'root' : 'graphite.rack')
            ->firstOrFail();
        if (!$guestAdminCredential) {
            $message = 'RunApplianceBootstrap failed for ' . $instance->id . ', no admin credentials found';
            Log::error($message);
            $this->fail(new \Exception($message));
            return;
        }

        $endpoint = ($instance->platform == 'Linux') ? 'linux/script' : 'windows/script';
        /** @var Response $deployResponse */
        $response = $instance->availabilityZone->kingpinService()->post(
            '/api/v2/vpc/' . $this->model->vpc->id . '/instance/' . $this->model->id . '/guest/' . $endpoint,
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

        // 0 = completed, 1 = not started, 2 = running, 3 = failed
        switch ($response->exitCode) {
            case 0:
                Log::info(get_class($this) . ': Readiness script for instance ' .  $instance->id . ' completed successfully', ['id' => $instance->id]);
                return;
            case 1:
                Log::info(get_class($this) . ': Readiness script for instance ' .  $instance->id . ' has not yet started, retrying in ' . $this->backoff . ' seconds', ['id' => $instance->id]);
                $this->release($this->backoff);
                return;
            case 2:
                Log::info(get_class($this) . ': Readiness script for instance ' .  $instance->id . ' still in progress, retrying in ' . $this->backoff . ' seconds', ['id' => $instance->id]);
                $this->release($this->backoff);
                return;
            case 3:
                Log::error(get_class($this) . ': Readiness script for instance ' .  $instance->id . ' failed', ['id' => $instance->id]);
                $this->fail(new \Exception('Readiness script for ' . $instance->id . ' failed with exit code 3. Output: ' . $response->output));
                return;
            default:
                Log::error(
                    get_class($this) . ': Readiness script returned an unexpected response',
                    [
                        'id' => $instance->id,
                        'exitCode' => $response->exitCode,
                        'output' => $response->output
                    ]
                );
                throw new \Exception('Readiness script for ' . $instance->id . ' returned an unexpected response: ' . $response->exitCode . ':' . $response->output);
        }
    }
}
