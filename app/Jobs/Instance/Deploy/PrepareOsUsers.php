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
            $message = 'PrepareOsDisk failed for ' . $instance->id . ', no admin credentials found';
            Log::error($message);
            $this->fail(new \Exception($message));
            return;
        }

        if ($instance->platform == 'Windows') {
            // Rename the Windows "administrator" account to "graphite.rack"
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

            // Add Windows user accounts
            collect([
                ['ukfast.support', $passwordService->generate()],
            ])->each(function ($username, $password) use ($instance, $vpc, $guestAdminCredential) {
                $credential = Credential::create([
                    'name' => $username,
                    'resource_id' => $instance->id,
                    'username' => $username,
                ]);
                $credential->password = $password;
                $credential->save();

                try {
                    /** @var Response $response */
                    $response = $instance->availabilityZone->kingpinService()->put(
                        '/api/v2/vpc/' . $vpc->id . '/instance/' . $instance->id . '/guest/windows/user',
                        [
                            'json' => [
                                'targetPassword' => $guestAdminCredential->username,
                                'targetUsername' => $guestAdminCredential->password,
                                'username' => $username,
                                'password' => $password,
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
            });
        } else {
            // Create Linux Admin Group
            try {
                /** @var Response $response */
                $response = $instance->availabilityZone->kingpinService()->put(
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

            // Add Linux user accounts
            collect([
                ['graphiterack', $passwordService->generate()],
                ['ukfastsupport', $passwordService->generate()],
            ])->each(function ($username, $password) use ($instance, $vpc, $guestAdminCredential) {
                $credential = Credential::create([
                    'name' => $username,
                    'resource_id' => $instance->id,
                    'username' => $username,
                ]);
                $credential->password = $password;
                $credential->save();

                try {
                    /** @var Response $response */
                    $response = $instance->availabilityZone->kingpinService()->put(
                        '/api/v2/vpc/' . $vpc->id . '/instance/' . $instance->id . '/guest/linux/user',
                        [
                            'json' => [
                                'targetPassword' => $guestAdminCredential->username,
                                'targetUsername' => $guestAdminCredential->password,
                                'username' => $username,
                                'password' => $password,
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
            });
        }
    }
}
