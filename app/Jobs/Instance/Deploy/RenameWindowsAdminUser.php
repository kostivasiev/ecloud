<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Image;
use App\Models\V2\Instance;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class RenameWindowsAdminUser extends Job
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

        if ($instance->platform != Image::PLATFORM_WINDOWS) {
            Log::info(get_class($this) . ' : Platform is not ' . Image::PLATFORM_WINDOWS .', skipping');
            return;
        }

        $guestAdminCredential = $instance->getGuestAdminCredentials();
        if (!$guestAdminCredential) {
            $message = get_class($this) . ' : Failed for ' . $instance->id . ', no admin credentials found';
            Log::error($message);
            $this->fail(new \Exception($message));
            return;
        }

        // Rename the Windows "administrator" account to "graphite.rack"
        Log::debug(get_class($this) . ' for instance ' . $instance->id . ' : Rename "Administrator" user');
        $instance->availabilityZone->kingpinService()->put(
            '/api/v2/vpc/' . $instance->vpc->id . '/instance/' . $instance->id . '/guest/windows/user/administrator/username',
            [
                'json' => [
                    'newUsername' => $guestAdminCredential->username,
                    'username' => 'administrator',
                    'password' => $guestAdminCredential->password,
                ],
            ]
        );
        Log::debug(get_class($this) . ' for instance ' . $instance->id . ' : Renamed "Administrator" user');
    }
}
