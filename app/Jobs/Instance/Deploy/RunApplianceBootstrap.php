<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use App\Models\V2\ImageParameter;
use App\Models\V2\Instance;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class RunApplianceBootstrap extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

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

        $imageData = $instance->deploy_data['image_data'] ?? [];

        $instance->image->imageParameters
            ->filter(function ($value) {
                return $value->type == ImageParameter::TYPE_PASSWORD;
            })->each(function ($passwordParameter) use ($instance, &$imageData) {
                $credential = $instance->credentials()->where('username', $passwordParameter->key)->first();
                if ($credential) {
                    $imageData[$passwordParameter->key] = $credential->password;
                }
            });

        $instance->availabilityZone->kingpinService()->post(
            '/api/v2/vpc/' . $this->model->vpc->id . '/instance/' . $this->model->id . '/guest/linux/script',
            [
                'json' => [
                    'encodedScript' => base64_encode(
                        (new \Mustache_Engine())->loadTemplate($this->model->image->script_template)
                            ->render($this->generateDefaultParameters($imageData))
                    ),
                    'username' => $guestAdminCredential->username,
                    'password' => $guestAdminCredential->password,
                ],
            ]
        );
    }

    /**
     * @param $imageData
     * @return mixed
     */
    protected function generateDefaultParameters($imageData)
    {
        if ($this->model->image->getMetadata('ukfast.license.type') == 'cpanel') {
            return $this->generateDefaultCpanelParameters($imageData);
        }

        return $imageData;
    }

    /**
     * @param $imageData
     * @return mixed
     */
    protected function generateDefaultCpanelParameters($imageData)
    {
        if (!in_array('cpanel_hostname', array_keys($imageData))) {
            $floatingIp = FloatingIp::findOrFail($this->model->deploy_data['floating_ip_id']);
            $imageData['cpanel_hostname'] = $floatingIp->ip_address . '.srvlist.ukfast.net';
        }

        return $imageData;
    }
}
