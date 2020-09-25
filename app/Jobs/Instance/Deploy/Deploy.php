<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Nic;
use App\Models\V2\Volume;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;
use App\Models\V2\Instance;
use App\Services\V2\KingpinService;

class Deploy extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        Log::info('Performing Deploy for instance '. $this->data['instance_id']);
        $logMessage = 'Deploy instance ' . $this->data['instance_id'] . ' : ';

        $instance = Instance::findOrFail($this->data['instance_id']);

        if (empty($instance->applianceVersion)) {
            $this->fail(new \Exception(
                'Deploy failed for ' . $instance->id . ', Failed to load appliance version'
            ));
            return;
        }

        $kingpinService = app()->make(KingpinService::class, [$instance->availabilityZone]);
        try {
            /** @var Response $deployResponse */
            $deployResponse = $kingpinService->post('/api/v2/vpc/' . $this->data['vpc_id'] . '/instance/fromtemplate', [
                'json' => [
                    'templateName' => $instance->applianceVersion->appliance_version_vm_template,
                    'instanceId' => $instance->getKey(),
                    'numCPU' => $instance->vcpu_cores,
                    'ramMib' => $instance->ram_capacity,
                    'resourceTierTags' => config('instance.resource_tier_tags')
                ]
            ]);

            if ($deployResponse->getStatusCode() != 200) {
                throw new \Exception('Invalid response status code: ' . $deployResponse->getStatusCode());
            }

            $deployResponse = json_decode($deployResponse->getBody()->getContents());
            if (!$deployResponse) {
                new \Exception('Deploy failed for ' . $instance->id . ', could not decode response');
            }

            Log::info($logMessage . 'Instance was deployed');

            Log::info($logMessage . count($deployResponse->volumes) . ' volume(s) found');
            // Create Volumes from kingpin
            foreach ($deployResponse->volumes as $volumeData) {
                $volume = Volume::withoutEvents(function () use ($instance, $volumeData) {
                    $volume = new Volume();
                    $volume::addCustomKey($volume);
                    $volume->name = $volume->id;
                    $volume->vpc()->associate($instance->vpc);
                    $volume->availability_zone_id = $instance->availability_zone_id;
                    $volume->capacity = $volumeData->sizeGiB;
                    $volume->vmware_uuid = $volumeData->uuid;
                    $volume->save();
                    return $volume;
                });

                Log::info($logMessage . 'Created volume resource ' . $volume->getKey() . ' for volume '. $volume->vmware_uuid);

                // Send created Volume ID's to Kinpin
                $volumeResponse = $kingpinService->put('/api/v1/vpc/' . $this->data['vpc_id'] . '/volume/' . $volume->vmware_uuid . '/resourceid', [
                    'json' => [
                        'volumeId' => $volume->getKey()
                    ]
                ]);

                if ($volumeResponse->getStatusCode() != 200) {
                    throw new \Exception('Invalid response status code ' . $volumeResponse->getStatusCode() .' whilst updating volume ' . $volume->vmware_uuid);
                }
                Log::info($logMessage . 'Volume ' . $volume->vmware_uuid . ' successfully updated with resource ID ' . $volume->getKey());
            }

            // Create NIC's
            Log::info($logMessage . count($deployResponse->nics) . ' NIC\'s found');
            foreach ($deployResponse->nics as $nicData) {
                $nic = new Nic([
                    'mac_address' => $nicData->macAddress,
                    'instance_id' => $instance->id,
                ]);
                $nic->network()->associate($this->data['network_id']);
                $nic->save();
                Log::info($logMessage . 'Created NIC resource ' . $nic->getKey());
            }
        } catch (GuzzleException $exception) {
            $error = $exception->getResponse()->getBody()->getContents();
            Log::info($logMessage . $error);
            $this->fail(new \Exception('Deploy failed for ' . $instance->id .' : '. $error));
            return;
        } catch (\Exception $exception) {
            Log::info($logMessage . $exception->getMessage());
            $this->fail(new \Exception('Deploy failed for ' . $instance->id .' : ' . $exception->getMessage()));
            return;
        }

        Log::info('Deploy finished successfully for instance ' . $instance->getKey());
    }
}
