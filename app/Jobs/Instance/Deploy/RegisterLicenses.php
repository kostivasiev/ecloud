<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use App\Models\V2\Instance;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use UKFast\Admin\Licenses\AdminClient;
use UKFast\SDK\Licenses\Entities\Key;
use UKFast\SDK\SelfResponse;

class RegisterLicenses extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    private Collection $imageMetadata;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    /**
     * Request a license from the licenses API
     * See https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/licenses/-/blob/master/openapi.yaml
     */
    public function handle()
    {
        $this->imageMetadata = $this->model->image->imageMetadata->pluck('key', 'value')->flip();

        if (!$this->imageMetadata->has('ukfast.license.identifier')) {
            return;
        }

        if ($this->imageMetadata->get('ukfast.license.type') == 'plesk') {
            $this->registerPleskLicense();
        }

        if ($this->imageMetadata->get('ukfast.license.type') == 'cpanel') {
            $this->registerCpanelLicense();
        }
    }

    protected function registerPleskLicense()
    {
        $instance = $this->model;
        $licensesAdminClient = app()->make(AdminClient::class)->setResellerId($instance->vpc->reseller_id);

        Log::info(get_class($this) . ' : Requesting Plesk license for instance ' . $instance->id);

        /** @var SelfResponse $response */
        $response = $licensesAdminClient
            ->plesk()
            ->requestLicense(
                $instance->id,
                'ecloud',
                $this->imageMetadata->get('ukfast.license.identifier')
            );

        Log::info(get_class($this) . ' : License ' . $response->getId() .' (Plesk) assigned to instance ' . $instance->id);

        /** @var Key $key */
        $key = $licensesAdminClient->licenses()->key($response->getId());

        if (empty($key->key)) {
            $this->fail(new \Exception('Failed to load Plesk license key'));
            return;
        }

        $deployData = $instance->deploy_data;
        $deployData['image_data']['plesk_key'] = $key->key;
        $instance->deploy_data = $deployData;
        $instance->save();

        Log::info(get_class($this) . ' : Plesk License ' . $response->getId() .' key added to instance ' . $instance->id . ' deploy data');
    }

    protected function registerCpanelLicense()
    {
        $instance = $this->model;
        $licensesAdminClient = app()->make(AdminClient::class)->setResellerId($instance->vpc->reseller_id);

        Log::info(get_class($this) . ' : Requesting cPanel license for instance ' . $instance->id);

        $floatingIp = FloatingIp::findOrFail($instance->deploy_data['floating_ip_id']);

        /** @var SelfResponse $response */
        $response = $licensesAdminClient
            ->cpanel()
            ->requestLicense(
                $instance->id,
                'ecloud',
                $floatingIp->ip_address,
                $this->imageMetadata->get('ukfast.license.identifier')
            );

        Log::info(get_class($this) . ' : License ' . $response->getId() .' (cPanel) assigned to instance ' . $instance->id);
    }
}
