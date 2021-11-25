<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Credential;
use App\Models\V2\FloatingIp;
use App\Models\V2\ImageParameter;
use App\Models\V2\Instance;
use App\Services\AccountsService;
use App\Services\V2\PasswordService;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class RunApplianceBootstrap extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    private $imageData;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
        $this->imageData = $instance->deploy_data['image_data'] ?? [];
        $this->getImageData();
    }

    protected function getImageData()
    {
        $this->getCpanelImageData();
        $this->getPleskImageData();
        $this->getPasswords();
    }

    /**
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/333
     */
    public function handle()
    {
        $instance = $this->model;

        if ($this->model->platform !== 'Linux') {
            Log::info('RunApplianceBootstrap for ' . $instance->id . ', nothing to do for non-Linux platforms, skipping');
            return;
        }

        if (empty($this->model->image->script_template)) {
            Log::info('RunApplianceBootstrap for ' . $instance->id . ', no script template defined, skipping');
            return;
        }

        $guestAdminCredential = $instance->credentials()
            ->where('username', ($instance->platform == 'Linux') ? 'root' : 'graphite.rack')
            ->firstOrFail();
        if (!$guestAdminCredential) {
            $message = 'RunApplianceBootstrap failed for ' . $instance->id . ', no admin credentials found';
            Log::error($message);
            $this->fail(new \Exception($message));
            return;
        }

        $endpoint = ($instance->platform == 'Linux') ? 'linux/script' : 'windows/script';
        $instance->availabilityZone->kingpinService()->post(
            '/api/v2/vpc/' . $this->model->vpc->id . '/instance/' . $this->model->id . '/guest/' . $endpoint,
            [
                'json' => [
                    'encodedScript' => base64_encode(
                        (new \Mustache_Engine())->loadTemplate($this->model->image->script_template)
                            ->render($this->imageData)
                    ),
                    'username' => $guestAdminCredential->username,
                    'password' => $guestAdminCredential->password,
                ],
            ]
        );
    }

    protected function getPleskImageData()
    {
        if ($this->model->image->getMetadata('ukfast.license.type') != 'plesk') {
            return;
        }

        if (!in_array('plesk_admin_email_address', array_keys($this->imageData)) || empty($this->imageData['plesk_admin_email_address'])) {
            $accountsService = app()->make(AccountsService::class);
            $this->imageData['plesk_admin_email_address'] = $accountsService->getPrimaryContactEmail($this->model->getResellerId());
            $deployData['image_data'] = $this->imageData;
            $this->model->setAttribute('deploy_data', $deployData)->saveQuietly();
        }

        if (!$this->model->credentials()
            ->where('name', '=', 'plesk_admin_password')
            ->exists()) {
            $credential = app()->make(Credential::class);
            $credential->fill([
                'name' => 'plesk_admin_password',
                'username' => 'plesk_admin_password',
                'password' => (new PasswordService())->generate(),
            ]);
            $credential->save();
            $this->model->credentials()->save($credential);
        }
    }

    protected function getCpanelImageData()
    {
        if ($this->model->image->getMetadata('ukfast.license.type') != 'cpanel') {
            return;
        }

        if (!in_array('cpanel_hostname', array_keys($this->imageData)) || empty($this->imageData['cpanel_hostname'])) {
            $floatingIp = FloatingIp::findOrFail($this->model->deploy_data['floating_ip_id']);
            $this->imageData['cpanel_hostname'] = $floatingIp->ip_address . '.srvlist.ukfast.net';
        }
    }

    protected function getPasswords()
    {
        $this->model->image->imageParameters
            ->filter(function ($value) {
                return $value->type == ImageParameter::TYPE_PASSWORD;
            })->each(function ($passwordParameter) {
                $credential = $this->model->credentials()->where('username', $passwordParameter->key)->first();
                if ($credential) {
                    $this->imageData[$passwordParameter->key] = $credential->password;
                }
            });
    }
}
