<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Image;
use App\Models\V2\Instance;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateLinuxAdminGroup extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    public function handle()
    {
        $instance = $this->model;

        if ($instance->platform != Image::PLATFORM_LINUX) {
            Log::info(get_class($this) . ' : Platform is not ' . Image::PLATFORM_LINUX . ', skipping');
            return;
        }

        $guestAdminCredential = $instance->getGuestAdminCredentials();

        if (!$guestAdminCredential) {
            $message = get_class($this) . ' : Failed for ' . $instance->id . ', no admin credentials found';
            Log::error($message);
            $this->fail(new \Exception($message));
            return;
        }

        // Create Linux Admin Group
        Log::debug(get_class($this) . ' for instance ' . $instance->id . ' : Creating Linux admin group');
        $instance->availabilityZone->kingpinService()->post(
            '/api/v2/vpc/' . $instance->vpc->id . '/instance/' . $instance->id . '/guest/linux/admingroup',
            [
                'json' => [
                    'username' => $guestAdminCredential->username,
                    'password' => $guestAdminCredential->password,
                ],
            ]
        );
        Log::debug(get_class($this) . ' for instance ' . $instance->id . ' : Created Linux admin group');
    }
}
