<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class ConfigureWinRm extends Job
{
    use Batchable;

    private $instance;

    public function __construct(Instance $instance)
    {
        $this->instance = $instance;
    }

    public function handle()
    {
        Log::debug(get_class($this) . ' : Started', ['id' => $this->instance->id]);

        $logMessage = 'ConfigureWinRm for ' . $this->instance->id . ': ';
        if ($this->instance->platform != 'Windows') {
            Log::info($logMessage . 'Platform is not Windows. Skipped.');
            return;
        }

        $guestAdminCredential = $this->instance->credentials()
            ->where('username', 'graphite.rack')
            ->firstOrFail();
        if (!$guestAdminCredential) {
            $message = 'ConfigureWinRm failed for ' . $this->instance->id . ', no admin credentials found';
            Log::error($message);
            $this->fail(new \Exception($message));
            return;
        }

        $this->instance->availabilityZone->kingpinService()->post(
            '/api/v2/vpc/' . $this->instance->vpc->id . '/instance/' . $this->instance->id . '/guest/windows/winrm',
            [
                'json' => [
                    'username' => $guestAdminCredential->username,
                    'password' => $guestAdminCredential->password,
                ],
            ]
        );

        Log::debug(get_class($this) . ' : Finished', ['id' => $this->instance->id]);
    }
}
