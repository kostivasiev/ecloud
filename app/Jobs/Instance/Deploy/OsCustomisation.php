<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Vpc;
use App\Services\V2\KingpinService;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;
use UKFast\Admin\Devices\AdminClient;

class OsCustomisation extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/331
     */
    public function handle()
    {
        Log::info('Starting OsCustomisation for instance '.$this->data['instance_id']);
        $instance = Instance::findOrFail($this->data['instance_id']);
        $vpc = Vpc::findOrFail($this->data['vpc_id']);
        $kingpinService = app()->make(KingpinService::class, [$instance->availabilityZone]);
        $devicesAdminClient = app()->make(AdminClient::class);
        $license = $devicesAdminClient->licenses()->getById(
            $instance->applianceVersions->appliance_version_server_license_id
        );

/*
^ UKFast\Admin\Devices\Entities\License^ {#1970
#attributes: array:4 [
    "id" => 258
    "name" => "CentOS 7 64-bit"
    "type" => "OS"
    "category" => "Linux"
  ]
  #dates: []
}
*/

        try {
            /** @var Response $response */
            $response = $kingpinService->post('/api/v2/vpc/'.$vpc->id.'/instance/'.$instance->id.'/power');
            if ($response->getStatusCode() == 200) {
                Log::info('PowerOn finished successfully for instance '.$instance->id);
                return;
            }
            $this->fail(new \Exception(
                'Failed to PowerOn '.$instance->id.', Kingpin status was '.$response->getStatusCode()
            ));
            return;
        } catch (GuzzleException $exception) {
            $this->fail(new \Exception(
                'Failed to PowerOn '.$instance->id.' : '.$exception->getResponse()->getBody()->getContents()
            ));
            return;
        }
    }
}