<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Vpc;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;

class UpdateNetworkAdapter extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/327
     */
    public function handle()
    {
        Log::info('Starting UpdateNetworkAdapter for instance ' . $this->data['instance_id']);
        $instance = Instance::findOrFail($this->data['instance_id']);
        $vpc = Vpc::findOrFail($this->data['vpc_id']);

        if (empty($instance->applianceVersion->appliance_version_vm_template)) {
            Log::info('Skipped UpdateNetworkAdapter for instance ' . $this->data['instance_id']);
            return;
        }

        foreach ($instance->nics as $nic) {
            try {
                /** @var Response $response */
                $response = $instance->availabilityZone->kingpinService()->put(
                    '/api/v2/vpc/' . $vpc->id . '/instance/' . $instance->id . '/nic/' . $nic->mac_address . '/connect',
                    [
                        'json' => [
                            'networkId' => $nic->network_id,
                        ],
                    ]
                );

                if ($response->getStatusCode() != 200) {
                    $message = 'Failed UpdateNetworkAdapter for ' . $instance->id;
                    Log::error($message, ['response' => $response]);
                    $this->fail(new \Exception($message));
                    return;
                }
            } catch (GuzzleException $exception) {
                $message = 'Failed UpdateNetworkAdapter for ' . $instance->id;
                Log::error($message, ['exception' => $exception]);
                $this->fail(new \Exception($message));
                return;
            }
        }

        Log::info('UpdateNetworkAdapter finished successfully for instance ' . $instance->id);
    }
}
