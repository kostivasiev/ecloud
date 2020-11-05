<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Jobs\TaskJob;
use App\Models\V2\Instance;
use App\Models\V2\Task;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;

class ActivateWindows extends TaskJob
{
    private $data;

    public function __construct(Task $task, $data)
    {
        parent::__construct($task);

        $this->data = $data;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['data' => $this->data]);

        $instance = Instance::findOrFail($this->data['instance_id']);
        $logMessage = 'ActivateWindows for ' . $instance->getKey() . ': ';
        if ($instance->platform != 'Windows') {
            Log::info($logMessage . 'Platform is not Windows. Skipped.');
            return;
        }

        $guestAdminCredential = $instance->credentials()
            ->where('username', 'graphite.rack')
            ->firstOrFail();
        if (!$guestAdminCredential) {
            $message = 'ActivateWindows failed for ' . $instance->id . ', no admin credentials found';
            Log::error($message);
            $this->fail(new \Exception($message));
            return;
        }

        $instance->availabilityZone->kingpinService()->post(
            '/api/v2/vpc/' . $instance->vpc->getKey() . '/instance/' . $instance->getKey() . '/guest/windows/activate',
            [
                'json' => [
                    'username' => $guestAdminCredential->username,
                    'password' => $guestAdminCredential->password,
                ],
            ]
        );

        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
