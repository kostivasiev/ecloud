<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Credential;
use App\Models\V2\Instance;
use App\Services\V2\PasswordService;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class PrepareOsUsers extends Job
{
    use Batchable;

    private $instance;

    public function __construct(Instance $instance)
    {
        $this->instance = $instance;
    }

    /**
     * @param PasswordService $passwordService
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/330
     */
    public function handle(PasswordService $passwordService)
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->instance->id]);

        $instance = $this->instance;

        $guestAdminCredential = $instance->credentials()
            ->where('username', ($instance->platform == 'Linux') ? 'root' : 'graphite.rack')
            ->firstOrFail();
        if (!$guestAdminCredential) {
            $message = 'PrepareOsUsers failed for ' . $instance->id . ', no admin credentials found';
            Log::error($message);
            $this->fail(new \Exception($message));
            return;
        }

        if ($instance->platform == 'Windows') {
            // Rename the Windows "administrator" account to "graphite.rack"
            Log::info('PrepareOsUsers for instance ' . $instance->id . ' : Rename "Administrator" user');
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
            Log::info('PrepareOsUsers for instance ' . $instance->id . ' : Renamed "Administrator" user');

            // Add Windows user accounts
            Log::info('PrepareOsUsers for instance ' . $instance->id . ' : Adding Windows accounts');
            collect([
                ['ukfast.support', $passwordService->generate()],
            ])->eachSpread(function ($username, $password) use ($instance, $guestAdminCredential) {
                Log::info('PrepareOsUsers for instance ' . $instance->id . ' : Creating Windows account "' . $username . '"');
                $credential = Credential::create([
                    'name' => $username,
                    'resource_id' => $instance->id,
                    'username' => $username,
                    'port' => $instance->platform == 'Linux' ? '2020' : '3389',
                ]);
                $credential->password = $password;
                $credential->save();
                Log::info('PrepareOsUsers for instance ' . $instance->id . ' : Created Windows account "' . $username . '"');

                Log::info('PrepareOsUsers for instance ' . $instance->id . ' : Pushing Windows account "' . $username . '"');
                $instance->availabilityZone->kingpinService()->post(
                    '/api/v2/vpc/' . $instance->vpc->id . '/instance/' . $instance->id . '/guest/windows/user',
                    [
                        'json' => [
                            'targetUsername' => $username,
                            'targetPassword' => $password,
                            'username' => $guestAdminCredential->username,
                            'password' => $guestAdminCredential->password,
                        ],
                    ]
                );
                Log::info('PrepareOsUsers for instance ' . $instance->id . ' : Pushed Windows account "' . $username . '"');
            });
        } else {
            // Create Linux Admin Group
            Log::info('PrepareOsUsers for instance ' . $instance->id . ' : Creating Linux admin group');
            $instance->availabilityZone->kingpinService()->post(
                '/api/v2/vpc/' . $instance->vpc->id . '/instance/' . $instance->id . '/guest/linux/admingroup',
                [
                    'json' => [
                        'username' => $guestAdminCredential->username,
                        'password' => $guestAdminCredential->password,
                    ],
                ]
            );
            Log::info('PrepareOsUsers for instance ' . $instance->id . ' : Created Linux admin group');

            // Add Linux user accounts
            Log::info('PrepareOsUsers for instance ' . $instance->id . ' : Adding Linux accounts');
            collect([
                ['graphiterack', $passwordService->generate()],
                ['ukfastsupport', $passwordService->generate()],
            ])->eachSpread(function ($username, $password) use ($instance, $guestAdminCredential) {
                Log::info('PrepareOsUsers for instance ' . $instance->id . ' : Creating Linux account "' . $username . '"');
                $credential = Credential::create([
                    'name' => $username,
                    'resource_id' => $instance->id,
                    'username' => $username,
                    'is_hidden' => $username === 'ukfastsupport',
                ]);
                $credential->password = $password;
                $credential->save();
                Log::info('PrepareOsUsers for instance ' . $instance->id . ' : Created Linux account "' . $username . '"');

                Log::info('PrepareOsUsers for instance ' . $instance->id . ' : Pushing Linux account "' . $username . '"');
                $instance->availabilityZone->kingpinService()->post(
                    '/api/v2/vpc/' . $instance->vpc->id . '/instance/' . $instance->id . '/guest/linux/user',
                    [
                        'json' => [
                            'targetUsername' => $username,
                            'targetPassword' => $password,
                            'username' => $guestAdminCredential->username,
                            'password' => $guestAdminCredential->password,
                        ],
                    ]
                );
                Log::info('PrepareOsUsers for instance ' . $instance->id . ' : Pushed Linux account "' . $username . '"');
            });
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->instance->id]);
    }
}
