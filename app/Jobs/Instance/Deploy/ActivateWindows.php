<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class ActivateWindows extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    public function handle()
    {
        $logMessage = 'ActivateWindows for ' . $this->model->id . ': ';
        if ($this->model->platform != 'Windows') {
            Log::info($logMessage . 'Platform is not Windows. Skipped.');
            return;
        }

        $guestAdminCredential = $this->model->credentials()
            ->where('username', 'graphite.rack')
            ->firstOrFail();
        if (!$guestAdminCredential) {
            $message = 'ActivateWindows failed for ' . $this->model->id . ', no admin credentials found';
            Log::error($message);
            $this->fail(new \Exception($message));
            return;
        }

        $this->model->availabilityZone->kingpinService()->post(
            '/api/v2/vpc/' . $this->model->vpc->id . '/instance/' . $this->model->id . '/guest/windows/activate',
            [
                'json' => [
                    'username' => $guestAdminCredential->username,
                    'password' => $guestAdminCredential->password,
                ],
            ]
        );
    }
}
