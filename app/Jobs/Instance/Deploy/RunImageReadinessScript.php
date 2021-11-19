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

    /**
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/333
     */
    public function handle()
    {
        $instance = $this->model;

        if (empty($this->model->image->readiness_script)) {
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

        /** @var Response $deployResponse */
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

        // 0 = completed, 1 = still processing, 2 = failed
        switch ($response->exitCode) {
            case 0:
                Log::info(get_class($this) . ': Readiness script completed successfully for instance ' .  $instance->id, ['id' => $instance->id]);
                return;
            case 1:
                Log::warning(get_class($this) . ': Readiness script still in progress, retrying in ' . $this->backoff . ' seconds', ['id' => $instance->id]);
                $this->release($this->backoff);
                return;
            case 2:
                Log::error(get_class($this) . ': Readiness script failed', ['id' => $this->model->id]);
                $this->fail(new \Exception('Readiness script for ' . $instance->id . ' failed with exit code 2. ' . $response->output));
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
                throw new \Exception('Readiness script for ' . $instance->id . ' failed with exit code 2. ' . $response->output);
        }
    }
}
