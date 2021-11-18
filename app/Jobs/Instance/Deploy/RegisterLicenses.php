<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;
use UKFast\Admin\Licenses\AdminClient;
use UKFast\SDK\Licenses\Entities\Key;
use UKFast\SDK\SelfResponse;

class RegisterLicenses extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

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
        $instance = $this->model;

        $imageMetadata = $instance->image->imageMetadata->pluck('key', 'value')->flip();

        if (!$imageMetadata->has('ukfast.license.identifier')) {
            return;
        }

        $licensesAdminClient = app()->make(AdminClient::class)->setResellerId($instance->vpc->reseller_id);

        if ($imageMetadata->get('ukfast.license.type') == 'plesk') {
            Log::info(get_class($this) . ' : Requesting Plesk license for instance ' . $instance->id);

            /** @var SelfResponse $response */
            $response = $licensesAdminClient
                ->plesk()
                ->requestLicense(
                    $instance->id,
                    'ecloud',
                    $imageMetadata->get('ukfast.license.identifier')
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

        // If it's a MSSQL license, then let's handle things slightly differently
        if ($imageMetadata->get('ukfast.license.type') == 'MSSQL2019') {
            Log::info(get_class($this) . ' : Submitting MSSQL license data for instance ' . $instance->id);

            try {
                $response = $licensesAdminClient->post('v1/licenses', json_encode([
                    'owner_id' => $instance->id,
                    'owner_type' => 'ecloud',
                    'key_id' => $imageMetadata->get('ukfast.license.identifier'),
                    'license_type' => $imageMetadata->get('ukfast.license.type'),
                    'reseller_id' => $instance->vpc->reseller_id
                ]));
            } catch (GuzzleException $exception) {
                $this->fail($exception);
            }

            $licenseId = (json_decode($response->getBody()->getContents()))->data->id;

            $deployData = $instance->deploy_data;
            $deployData['image_data']['license_type'] = $imageMetadata->get('ukfast.license.type');
            $deployData['image_data']['license_id'] = $licenseId;
            $instance->deploy_data = $deployData;
            $instance->save();

            Log::info(
                get_class($this) . ' : '.$imageMetadata->get('ukfast.license.type').' License '.
                $licenseId .' key added to instance ' . $instance->id . ' deploy data'
            );
        }
    }
}
