<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Credential;
use App\Models\V2\FloatingIp;
use App\Models\V2\ImageParameter;
use App\Models\V2\Instance;
use App\Services\V2\PasswordService;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;
use UKFast\Admin\Account\AdminClient;

class RunApplianceBootstrap extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    public $imageData;
    private $deployData;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    /**
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/333
     */
    public function handle()
    {
        $instance = $this->model;
        $this->imageData = $instance->deploy_data['image_data'] ?? [];
        $this->getImageData();

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

        Log::info(
            get_class($this) . ': Appliance bootstrap on instance ' . $instance->id . ' started.' .
            ' image_data = ' . json_encode($this->imageData),
            [
                'id' => $instance->id
            ]
        );

        $endpoint = ($instance->platform == 'Linux') ? 'linux/script' : 'windows/script';
        $response = $instance->availabilityZone->kingpinService()->post(
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
        $response = json_decode($response?->getBody()?->getContents());
        if ($response->exitCode !== 0) {
            Log::info('Non-zero exit code received in response', [
                'exit_code' => $response->exitCode,
                'output' => $response->output,
            ]);
            $this->fail(new \Exception($response->output));
        }
    }

    protected function getImageData()
    {
        $this->getPasswords();
        $this->getCpanelImageData();
        $this->getPleskImageData();
    }

    protected function getPleskImageData()
    {
        if ($this->model->image->getMetadata('ukfast.license.type') != 'plesk') {
            return;
        }

        $deployData = $this->model->deploy_data;
        if (!in_array('plesk_admin_email_address', array_keys($this->imageData)) ||
            empty($this->imageData['plesk_admin_email_address'])
        ) {
            $adminClient = app()->make(AdminClient::class)->setResellerId($this->model->getResellerId());
            $primaryContactId = ($adminClient->customers()->getById($this->model->getResellerId()))
                ->primaryContactId;
            $primaryContactEmail = $adminClient->contacts()->getById($primaryContactId)->emailAddress;
            $this->imageData['plesk_admin_email_address'] = $primaryContactEmail;
            $deployData['image_data'] = $this->imageData;
            $this->model->setAttribute('deploy_data', $deployData)->saveQuietly();
        }

        $this->imageData['plesk_admin_password'] ??= (new PasswordService())->generate();

        $credential = app()->make(Credential::class);
        $credential->fill([
            'name' => 'Plesk Administrator',
            'username' => 'admin',
            'password' => $this->imageData['plesk_admin_password'],
            'port' => config('plesk.admin.port', 8880),
        ]);
        $this->model->credentials()->save($credential);
    }

    protected function getCpanelImageData()
    {
        if ($this->model->image->getMetadata('ukfast.license.type') != 'cpanel') {
            return;
        }

        if (!in_array('cpanel_hostname', array_keys($this->imageData)) || empty($this->imageData['cpanel_hostname'])) {
            $floatingIp = FloatingIp::find($this->model->deploy_data['floating_ip_id']);
            if (!$floatingIp) {
                throw new \Exception('Floating Ip ' . $this->model->deploy_data['floating_ip_id'] . ' not found');
            }
            $this->imageData['cpanel_hostname'] = $floatingIp->ip_address . '.srvlist.ukfast.net';
        }
    }

    protected function getPasswords()
    {
        $this->model->image->imageParameters
            ->filter(function ($value) {
                return $value->type == ImageParameter::TYPE_PASSWORD;
            })->each(function ($passwordParameter) {
                $credential = $this->model->credentials()
                    ->where('is_hidden', true)
                    ->where('username', $passwordParameter->key)
                    ->first();
                if ($credential) {
                    $this->imageData[$passwordParameter->key] = $credential->password;
                }
            });
    }
}
