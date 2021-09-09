<?php
namespace App\Jobs\VpnSession;

use App\Jobs\Job;
use App\Models\V2\Credential;
use App\Models\V2\VpnSession;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Str;

class CreatePreSharedKey extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    public function __construct(VpnSession $vpnSession)
    {
        $this->model = $vpnSession;
    }

    public function handle()
    {
        $vpnSession = $this->model;

        if (!$vpnSession->credentials()->where('username', VpnSession::CREDENTIAL_PSK_USERNAME)->exists()) {
            $credential = new Credential(
                [
                    'name' => 'Pre-shared Key for VPN Session ' . $vpnSession->id,
                    'host' => null,
                    'username' => VpnSession::CREDENTIAL_PSK_USERNAME,
                    'password' => Str::random(32),
                    'port' => null,
                    'is_hidden' => true,
                ]
            );
            $vpnSession->credentials()->save($credential);
        }
    }
}
