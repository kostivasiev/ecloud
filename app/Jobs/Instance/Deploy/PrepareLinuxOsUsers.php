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

class PrepareLinuxOsUsers extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    /**
     * @param PasswordService $passwordService
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/330
     */
    public function handle(PasswordService $passwordService)
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

        // Add Linux user accounts
        Log::debug(get_class($this) . ' for instance ' . $instance->id . ' : Adding Linux accounts');

        collect([
            ['graphiterack', $passwordService->generate(), false, true],
            ['ukfastsupport', $passwordService->generate(), true, true],
            ['logic.monitor.' . $instance->id, $passwordService->generate(24), true, false],
        ])->eachSpread(function ($username, $password, $hidden, $sudo) use ($instance, $guestAdminCredential) {
            Log::debug(get_class($this) . ' for instance ' . $instance->id . ' : Creating Linux account "' . $username . '"');
            $credential = app()->make(Credential::class);
            $credential->fill([
                'name' => $username,
                'resource_id' => $instance->id,
                'username' => $username,
                'is_hidden' => $hidden,
                'port' => '2020',
            ]);
            $credential->password = $password;
            $credential->save();
            Log::debug(get_class($this) . ' for instance ' . $instance->id . ' : Created Linux account "' . $username . '"');

            Log::debug(get_class($this) . ' for instance ' . $instance->id . ' : Pushing Linux account "' . $username . '"');
            $instance->availabilityZone->kingpinService()->post(
                '/api/v2/vpc/' . $instance->vpc->id . '/instance/' . $instance->id . '/guest/linux/user',
                [
                    'json' => [
                        'targetUsername' => $username,
                        'targetPassword' => $credential->password,
                        'targetSudo' => $sudo,
                        'username' => $guestAdminCredential->username,
                        'password' => $guestAdminCredential->password,
                    ],
                ]
            );
            Log::debug(get_class($this) . ' for instance ' . $instance->id . ' : Pushed Linux account "' . $username . '"');
        });
    }
}
