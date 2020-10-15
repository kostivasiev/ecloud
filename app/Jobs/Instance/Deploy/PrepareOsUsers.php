<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Credential;
use App\Models\V2\Instance;
use App\Models\V2\Vpc;
use App\Services\V2\PasswordService;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;

class PrepareOsUsers extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @param PasswordService $passwordService
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/330
     */
    public function handle(PasswordService $passwordService)
    {
        Log::info('Starting PrepareOsUsers for instance ' . $this->data['instance_id']);
        $instance = Instance::findOrFail($this->data['instance_id']);
        $vpc = Vpc::findOrFail($instance->vpc->id);

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
            Log::info('PrepareOsUsers for instance ' . $this->data['instance_id'] . ' : Rename "Administrator" user');
            try {
                /** @var Response $response */
                $response = $instance->availabilityZone->kingpinService()->put(
                    '/api/v2/vpc/' . $vpc->id . '/instance/' . $instance->id . '/guest/windows/user/administrator/username',
                    [
                        'json' => [
                            'newUsername' => $guestAdminCredential->username,
                            'username' => 'administrator',
                            'password' => $guestAdminCredential->password,
                        ],
                    ]
                );
                if ($response->getStatusCode() != 200) {
                    $message = 'Failed PrepareOsUsers for ' . $instance->id;
                    Log::error($message, ['response' => $response]);
                    $this->fail(new \Exception($message));
                    return;
                }
            } catch (GuzzleException $exception) {
                $message = 'Failed PrepareOsUsers for ' . $instance->id;
                Log::error($message, ['exception' => $exception]);
                $this->fail(new \Exception($message));
                return;
            }
            Log::info('PrepareOsUsers for instance ' . $this->data['instance_id'] . ' : Renamed "Administrator" user');

            // Add Windows user accounts
            Log::info('PrepareOsUsers for instance ' . $this->data['instance_id'] . ' : Adding Windows accounts');
            collect([
                ['ukfast.support', $passwordService->generate()],
            ])->eachSpread(function ($username, $password) use ($instance, $vpc, $guestAdminCredential) {
                Log::info('PrepareOsUsers for instance ' . $this->data['instance_id'] . ' : Creating Windows account "' . $username . '"');
                $credential = Credential::create([
                    'name' => $username,
                    'resource_id' => $instance->id,
                    'username' => $username,
                ]);
                $credential->password = $password;
                $credential->save();
                Log::info('PrepareOsUsers for instance ' . $this->data['instance_id'] . ' : Created Windows account "' . $username . '"');

                Log::info('PrepareOsUsers for instance ' . $this->data['instance_id'] . ' : Pushing Windows account "' . $username . '"');
                try {
                    /** @var Response $response */
                    $response = $instance->availabilityZone->kingpinService()->post(
                        '/api/v2/vpc/' . $vpc->id . '/instance/' . $instance->id . '/guest/windows/user',
                        [
                            'json' => [
                                'targetUsername' => $username,
                                'targetPassword' => $password,
                                'username' => $guestAdminCredential->username,
                                'password' => $guestAdminCredential->password,
                            ],
                        ]
                    );
                    if ($response->getStatusCode() != 200) {
                        $message = 'Failed PrepareOsUsers for ' . $instance->id .
                            ' when creating Windows account for "' . $username . '"';
                        Log::error($message, ['response' => $response]);
                        $this->fail(new \Exception($message));
                        return;
                    }
                } catch (GuzzleException $exception) {
                    $message = 'Failed PrepareOsUsers for ' . $instance->id .
                        ' when creating Windows account for "' . $username . '"';
                    Log::error($message, ['exception' => $exception]);
                    $this->fail(new \Exception($message));
                    return;
                }
                Log::info('PrepareOsUsers for instance ' . $this->data['instance_id'] . ' : Pushed Windows account "' . $username . '"');
            });
        } else {
            // Create Linux Admin Group
            Log::info('PrepareOsUsers for instance ' . $this->data['instance_id'] . ' : Creating Linux admin group');
            try {
                /** @var Response $response */
                $response = $instance->availabilityZone->kingpinService()->post(
                    '/api/v2/vpc/' . $vpc->id . '/instance/' . $instance->id . '/guest/linux/admingroup',
                    [
                        'json' => [
                            'username' => $guestAdminCredential->username,
                            'password' => $guestAdminCredential->password,
                        ],
                    ]
                );
                if ($response->getStatusCode() != 200) {
                    $message = 'Failed PrepareOsUsers for ' . $instance->id . ' when creating Linux admin group';
                    Log::error($message, ['response' => $response]);
                    $this->fail(new \Exception($message));
                    return;
                }
            } catch (GuzzleException $exception) {
                $message = 'Failed PrepareOsUsers for ' . $instance->id . ' when creating Linux admin group';
                Log::error($message, ['exception' => $exception]);
                $this->fail(new \Exception($message));
                return;
            }
            Log::info('PrepareOsUsers for instance ' . $this->data['instance_id'] . ' : Created Linux admin group');

            // Add Linux user accounts
            Log::info('PrepareOsUsers for instance ' . $this->data['instance_id'] . ' : Adding Linux accounts');
            collect([
                ['graphiterack', $passwordService->generate()],
                ['ukfastsupport', $passwordService->generate()],
            ])->eachSpread(function ($username, $password) use ($instance, $vpc, $guestAdminCredential) {
                Log::info('PrepareOsUsers for instance ' . $this->data['instance_id'] . ' : Creating Linux account "' . $username . '"');
                $credential = Credential::create([
                    'name' => $username,
                    'resource_id' => $instance->id,
                    'username' => $username,
                ]);
                $credential->password = $password;
                $credential->save();
                Log::info('PrepareOsUsers for instance ' . $this->data['instance_id'] . ' : Created Linux account "' . $username . '"');

                Log::info('PrepareOsUsers for instance ' . $this->data['instance_id'] . ' : Pushing Linux account "' . $username . '"');
                try {
                    /** @var Response $response */
                    $response = $instance->availabilityZone->kingpinService()->post(
                        '/api/v2/vpc/' . $vpc->id . '/instance/' . $instance->id . '/guest/linux/user',
                        [
                            'json' => [
                                'targetUsername' => $username,
                                'targetPassword' => $password,
                                'username' => $guestAdminCredential->username,
                                'password' => $guestAdminCredential->password,
                            ],
                        ]
                    );
                    if ($response->getStatusCode() != 200) {
                        $message = 'Failed PrepareOsUsers for ' . $instance->id .
                            ' when creating Linux account for "' . $username . '"';
                        Log::error($message, ['response' => $response]);
                        $this->fail(new \Exception($message));
                        return;
                    }
                } catch (GuzzleException $exception) {
                    $message = 'Failed PrepareOsUsers for ' . $instance->id .
                        ' when creating Linux account for "' . $username . '"';
                    Log::error($message, ['exception' => $exception]);
                    $this->fail(new \Exception($message));
                    return;
                }
                Log::info('PrepareOsUsers for instance ' . $this->data['instance_id'] . ' : Pushed Linux account "' . $username . '"');
            });
        }
    }
}
