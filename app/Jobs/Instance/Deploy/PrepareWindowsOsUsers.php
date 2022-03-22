<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Credential;
use App\Models\V2\Image;
use App\Models\V2\Instance;
use App\Services\V2\PasswordService;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class PrepareWindowsOsUsers extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    /**
     * Add Windows user accounts
     * @param PasswordService $passwordService
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/330
     */
    public function handle(PasswordService $passwordService)
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

        Log::debug(get_class($this) . ' for instance ' . $instance->id . ' : Adding Windows accounts');

        collect([
            ['ukfast.support', $passwordService->generate()],
            ['lm.' . $instance->id, $passwordService->generate(24)],
        ])->eachSpread(function ($username, $password) use ($instance, $guestAdminCredential) {
            Log::debug(get_class($this) . ' for instance ' . $instance->id . ' : Creating Windows account "' . $username . '"');

            $credential = app()->make(Credential::class);
            $credential->fill([
                'name' => $username,
                'resource_id' => $instance->id,
                'username' => $username,
                'is_hidden' => true,
                'port' => '3389',
            ]);
            $credential->password = $password;
            $credential->save();
            Log::debug(get_class($this) . ' for instance ' . $instance->id . ' : Created Windows account "' . $username . '"');

            Log::debug(get_class($this) . ' for instance ' . $instance->id . ' : Pushing Windows account "' . $username . '"');
            $instance->availabilityZone->kingpinService()->post(
                '/api/v2/vpc/' . $instance->vpc->id . '/instance/' . $instance->id . '/guest/windows/user',
                [
                    'json' => [
                        'targetUsername' => $username,
                        'targetPassword' => $credential->password,
                        'username' => $guestAdminCredential->username,
                        'password' => $guestAdminCredential->password,
                    ],
                ]
            );
            Log::debug(get_class($this) . ' for instance ' . $instance->id . ' : Pushed Windows account "' . $username . '"');
        });
    }
}
