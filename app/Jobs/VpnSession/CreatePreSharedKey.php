<?php
namespace App\Jobs\VpnSession;

use App\Jobs\TaskJob;
use App\Models\V2\Credential;
use App\Models\V2\VpnSession;
use App\Services\V2\PasswordService;

class CreatePreSharedKey extends TaskJob
{
    public function handle(PasswordService $passwordService)
    {
        $vpnSession = $this->task->resource;
        $passwordService->special = true;

        if (!$vpnSession->credentials()->where('username', VpnSession::CREDENTIAL_PSK_USERNAME)->exists()) {
            $credential = new Credential(
                [
                    'name' => 'Pre-shared Key for VPN Session ' . $vpnSession->id,
                    'host' => null,
                    'username' => VpnSession::CREDENTIAL_PSK_USERNAME,
                    'password' => $passwordService->generate(32),
                    'port' => null,
                    'is_hidden' => true,
                ]
            );
            $vpnSession->credentials()->save($credential);
        }
    }
}
