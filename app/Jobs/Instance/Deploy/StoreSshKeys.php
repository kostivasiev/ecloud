<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Credential;
use App\Models\V2\Image;
use App\Models\V2\Instance;
use App\Models\V2\SshKeyPair;
use App\Services\V2\PasswordService;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class StoreSshKeys extends Job
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

        if (empty($this->model->deploy_data['ssh_key_pair_ids'])) {
            Log::info(get_class($this) . ' : No SSH Keys specified, skipping');
            return;
        }

        $guestAdminCredential = $instance->getGuestAdminCredentials();

        if (!$guestAdminCredential) {
            $message = get_class($this) . ' : Failed for ' . $instance->id . ', no admin credentials found';
            Log::error($message);
            $this->fail(new \Exception($message));
            return;
        }

        $sshKeys = [];

        foreach ($this->model->deploy_data['ssh_key_pair_ids'] as $sshKeyPairId) {
            $sshKeyPair = SshKeyPair::find($sshKeyPairId);
            if (!$sshKeyPair) {
                Log::warning('Cannot find SSH keypair with id "' . $sshKeyPairId . '"');
                continue;
            }

            $sshKeys[] = $sshKeyPair->public_key;
        }

        if (count($sshKeys) > 0) {
            Log::debug(get_class($this) . ' for instance ' . $instance->id . ' : Pushing SSH keys to user "' . $guestAdminCredential->username . '"');

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
            } catch (ClientException|RequestException $e) {
                Log::warning('Failed to set SSH keys for instance ' . $this->model->id, [
                    'detail' => $e,
                ]);
            }

            Log::debug(get_class($this) . ' for instance ' . $instance->id . ' : Pushed SSH keys to user "' . $guestAdminCredential->username . '"');
        }
    }
}
