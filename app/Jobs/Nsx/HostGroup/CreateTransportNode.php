<?php

namespace App\Jobs\Nsx\HostGroup;

use App\Jobs\Job;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\HostGroup;
use App\Models\V2\Vpc;
use Illuminate\Support\Facades\Log;

class CreateTransportNode extends Job
{
    private $model;

    public function __construct(HostGroup $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $hostGroup = $this->model;

        $transportNodeProfiles = $this->getTransportNodeProfiles($hostGroup->availabilityZone);
        if ($transportNodeProfiles === null) {
            $this->fail(new \Exception('Failed to get TransportNodeProfiles'));
            return false;
        }

        $transportNodeProfileDisplayName = $this->model->id . '-tnp';
        $exists = collect($transportNodeProfiles['results'])->filter(function ($result) use (
            $transportNodeProfileDisplayName
        ) {
            return ($result['display_name'] === $transportNodeProfileDisplayName);
        })->count();
        if ($exists) {
            Log::info(get_class($this) . ' : Skipped', [
                'id' => $this->model->id,
            ]);
            return true;
        }

        $networkSwitch = $this->getNetworkSwitchDetails($hostGroup->availabilityZone, $hostGroup->vpc);
        if ($networkSwitch === null) {
            $this->fail(new \Exception('Failed to get NetworkSwitch'));
            return false;
        }

        $transportZones = $this->getTransportZones($hostGroup->availabilityZone);
        if (!isset($transportZones['results']) || !count($transportZones['results'])) {
            $this->fail(new \Exception('Failed to get TransportZones'));
            return false;
        }
        $transportZone = collect($transportZones['results'])->first();

        $uplinkHostSwitchProfiles = $this->getUplinkHostSwitchProfiles($hostGroup->availabilityZone);
        if (!isset($uplinkHostSwitchProfiles['results']) || !count($uplinkHostSwitchProfiles['results'])) {
            $this->fail(new \Exception('Failed to get UplinkHostSwitchProfiles'));
            return false;
        }
        $uplinkHostSwitchProfile = collect($uplinkHostSwitchProfiles['results'])->first();

        $response = $hostGroup->availabilityZone->nsxService()->post(
            '/api/v1/transport-node-profiles',
            [
                'json' => [
                    'resource_type' => 'TransportNodeProfile',
                    'display_name' => $transportNodeProfileDisplayName,
                    'description' => 'API created Transport Node Profile',
                    'host_switch_spec' => [
                        'resource_type' => 'StandardHostSwitchSpec',
                        'host_switches' => [
                            [
                                'host_switch_name' => $hostGroup->vpc->id,
                                'host_switch_id' => $networkSwitch['uuid'],
                                'host_switch_mode' => 'STANDARD',
                                'host_switch_type' => 'VDS',
                                'host_switch_profile_ids' => [
                                    [
                                        'value' => $uplinkHostSwitchProfile['id'],
                                        'key' => 'UplinkHostSwitchProfile'
                                    ]
                                ],
                                'transport_zone_endpoints' => [
                                    [
                                        'transport_zone_id' => $transportZone['id'],
                                        'transport_zone_profile_ids' => $transportZone['transport_zone_profile_ids'],
                                    ]
                                ],
                                'uplinks' => [
                                    [
                                        'vds_uplink_name' => 'dvUplink1',
                                        'uplink_name' => 'Uplink 1'
                                    ],
                                    [
                                        'vds_uplink_name' => 'dvUplink2',
                                        'uplink_name' => 'Uplink 2'
                                    ]
                                ],
                                'ip_assignment_spec' => [
                                    'resource_type' => 'AssignedByDhcp'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        );

        if (!$response || $response->getStatusCode() !== 200) {
            Log::error(get_class($this) . ' : Failed', [
                'id' => $this->model->id,
                'status_code' => $response->getStatusCode(),
                'content' => $response->getBody()->getContents()
            ]);
            $this->fail(new \Exception('Failed to create "' . $this->model->id . '"'));
            return false;
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }

    private function getTransportNodeProfiles(AvailabilityZone $availabilityZone): ?array
    {
        $response = $availabilityZone->nsxService()
            ->get('/api/v1/transport-node-profiles');
        if (!$response || $response->getStatusCode() !== 200) {
            return null;
        }
        $json = json_decode($response->getBody()->getContents(), true);
        return (!$json) ? null : $json;
    }

    private function getNetworkSwitchDetails(AvailabilityZone $availabilityZone, Vpc $vpc): ?array
    {
        $response = $availabilityZone->kingpinService()
            ->get('/api/v1/vpc/' . $vpc->id . '/network/switch');
        if (!$response || $response->getStatusCode() !== 200) {
            return null;
        }
        $json = json_decode($response->getBody()->getContents(), true);
        return (!$json) ? null : $json;
    }

    private function getTransportZones(AvailabilityZone $availabilityZone): ?array
    {
        $response = $availabilityZone->nsxService()
            ->get('/api/v1/search/query?query=resource_type:TransportZone%20AND%20tags.scope:ukfast%20AND%20tags.tag:default-overlay-tz');
        if (!$response || $response->getStatusCode() !== 200) {
            return null;
        }
        $json = json_decode($response->getBody()->getContents(), true);
        return (!$json) ? null : $json;
    }

    private function getUplinkHostSwitchProfiles(AvailabilityZone $availabilityZone): ?array
    {
        $response = $availabilityZone->nsxService()
            ->get('/api/v1/search/query?query=resource_type:UplinkHostSwitchProfile%20AND%20tags.scope:ukfast%20AND%20tags.tag:default-uplink-profile');
        if (!$response || $response->getStatusCode() !== 200) {
            return null;
        }
        $json = json_decode($response->getBody()->getContents(), true);
        return (!$json) ? null : $json;
    }

    public function failed($exception)
    {
        $this->model->setSyncFailureReason($exception->getMessage());
    }
}
