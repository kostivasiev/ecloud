<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Credential;
use App\Models\V2\Instance;
use App\Models\V2\SshKeyPair;
use App\Services\V2\PasswordService;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class PrepareOsUsers extends Job
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
            Log::debug('PrepareOsUsers for instance ' . $instance->id . ' : Rename "Administrator" user');
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
            Log::debug('PrepareOsUsers for instance ' . $instance->id . ' : Renamed "Administrator" user');

            // Add Windows user accounts
            Log::debug('PrepareOsUsers for instance ' . $instance->id . ' : Adding Windows accounts');
            collect([
                ['ukfast.support', $passwordService->generate()],
            ])->eachSpread(function ($username, $password) use ($instance, $guestAdminCredential) {
                Log::debug('PrepareOsUsers for instance ' . $instance->id . ' : Creating Windows account "' . $username . '"');
                $credential = Credential::create([
                    'name' => $username,
                    'resource_id' => $instance->id,
                    'username' => $username,
                    'port' => $instance->platform == 'Linux' ? '2020' : '3389',
                ]);
                $credential->password = $password;
                $credential->save();
                Log::debug('PrepareOsUsers for instance ' . $instance->id . ' : Created Windows account "' . $username . '"');

                Log::debug('PrepareOsUsers for instance ' . $instance->id . ' : Pushing Windows account "' . $username . '"');
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
                Log::debug('PrepareOsUsers for instance ' . $instance->id . ' : Pushed Windows account "' . $username . '"');
            });
        } else {
            // Create Linux Admin Group
            Log::debug('PrepareOsUsers for instance ' . $instance->id . ' : Creating Linux admin group');
            $instance->availabilityZone->kingpinService()->post(
                '/api/v2/vpc/' . $instance->vpc->id . '/instance/' . $instance->id . '/guest/linux/admingroup',
                [
                    'json' => [
                        'username' => $guestAdminCredential->username,
                        'password' => $guestAdminCredential->password,
                    ],
                ]
            );
            Log::debug('PrepareOsUsers for instance ' . $instance->id . ' : Created Linux admin group');

            // Add Linux user accounts
            Log::debug('PrepareOsUsers for instance ' . $instance->id . ' : Adding Linux accounts');
            collect([
                ['graphiterack', $passwordService->generate()],
                ['ukfastsupport', $passwordService->generate()],
            ])->eachSpread(function ($username, $password) use ($instance, $guestAdminCredential) {
                Log::debug('PrepareOsUsers for instance ' . $instance->id . ' : Creating Linux account "' . $username . '"');
                $credential = Credential::create([
                    'name' => $username,
                    'resource_id' => $instance->id,
                    'username' => $username,
                    'is_hidden' => $username === 'ukfastsupport',
                ]);
                $credential->password = $password;
                $credential->save();
                Log::debug('PrepareOsUsers for instance ' . $instance->id . ' : Created Linux account "' . $username . '"');

                Log::debug('PrepareOsUsers for instance ' . $instance->id . ' : Pushing Linux account "' . $username . '"');
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
                Log::debug('PrepareOsUsers for instance ' . $instance->id . ' : Pushed Linux account "' . $username . '"');
            });

            if (isset($this->model->deploy_data['ssh_key_pairs'])) {
                $sshKeys = [];

                foreach ($this->model->deploy_data['ssh_key_pairs'] as $sshKeyPairId) {
                    $sshKeyPair = SshKeyPair::find($sshKeyPairId);
                    if (!$sshKeyPair) {
                        Log::warning('Cannot find SSH keypair with id "' . $sshKeyPairId . '"');
                        continue;
                    }

                    $sshKeys[] = $sshKeyPair->first()->public_key;
                }

                if (count($sshKeys) > 0) {
                    Log::debug('PrepareOsUsers for instance ' . $instance->id . ' : Pushing SSH keys to user "' . $guestAdminCredential->username . '"');

                    try {
                        $instance->availabilityZone->kingpinService()->post(
                            '/api/v2/vpc/' . $instance->vpc->id . '/instance/' . $instance->id . '/guest/linux/user',
                            [
                                'json' => [
                                    'targetUsername' => $guestAdminCredential->username,
                                    'targetPublicKeys' => $sshKeys,
                                    'username' => $guestAdminCredential->username,
                                    'password' => $guestAdminCredential->password,
                                ],
                            ]
                        );
                    } catch (ClientException | RequestException $e) {
                        Log::warning('Failed to set SSH keys for instance ' . $this->model->id, [
                            'detail' => $e,
                        ]);
                    }

                    Log::debug('PrepareOsUsers for instance ' . $instance->id . ' : Pushed SSH keys to user "' . $guestAdminCredential->username . '"');
                }
            }
        }
    }
}
