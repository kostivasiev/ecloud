<?php

namespace App\Jobs\Nsx\HostGroup;

use App\Jobs\TaskJob;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Vpc;

class CreateTransportNodeProfile extends TaskJob
{
    public function handle()
    {
        $hostGroup = $this->task->resource;

        $transportNodeProfiles = $this->getTransportNodeProfiles($hostGroup->availabilityZone);
        if (!$transportNodeProfiles) {
            $this->fail(new \Exception('Failed to get TransportNodeProfiles'));
            return false;
        }

        $transportNodeProfileDisplayName =  'tnp-' . $hostGroup->id;
        $exists = collect($transportNodeProfiles->results)->filter(function ($result) use (
            $transportNodeProfileDisplayName
        ) {
            return ($result->display_name === $transportNodeProfileDisplayName);
        })->count();
        if ($exists) {
            $this->debug('Already exists, skipping');
            return true;
        }

        $networkSwitch = $this->getNetworkSwitchDetails($hostGroup->availabilityZone, $hostGroup->vpc);
        if (!$networkSwitch) {
            $this->fail(new \Exception('Failed to get NetworkSwitch'));
            return false;
        }

        $transportZones = $this->getTransportZones($hostGroup->availabilityZone);
        if (!$transportZones || !isset($transportZones->results) || !count($transportZones->results)) {
            $this->fail(new \Exception('Failed to get TransportZones'));
            return false;
        }
        $transportZone = collect($transportZones->results)->first();

        $uplinkHostSwitchProfiles = $this->getUplinkHostSwitchProfiles($hostGroup->availabilityZone);
        if (!$uplinkHostSwitchProfiles || !isset($uplinkHostSwitchProfiles->results) || !count($uplinkHostSwitchProfiles->results)) {
            $this->fail(new \Exception('Failed to get UplinkHostSwitchProfiles'));
            return false;
        }
        $uplinkHostSwitchProfile = collect($uplinkHostSwitchProfiles->results)->first();

        $vtepIpPools = $this->getVtepIpPools($hostGroup->availabilityZone);
        if (!$vtepIpPools || !isset($vtepIpPools->results) || !count($vtepIpPools->results)) {
            $this->fail(new \Exception('Failed to get VtepIpPools'));
            return false;
        }
        $vtepIpPool = collect($vtepIpPools->results)->first();

        $hostGroup->availabilityZone->nsxService()->post(
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
                                'host_switch_id' => $networkSwitch->uuid,
                                'host_switch_mode' => 'STANDARD',
                                'host_switch_type' => 'VDS',
                                'host_switch_profile_ids' => [
                                    [
                                        'value' => $uplinkHostSwitchProfile->id,
                                        'key' => 'UplinkHostSwitchProfile'
                                    ]
                                ],
                                'transport_zone_endpoints' => [
                                    [
                                        'transport_zone_id' => $transportZone->id,
                                        'transport_zone_profile_ids' => $transportZone->transport_zone_profile_ids,
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
                                    'resource_type' => 'StaticIpPoolSpec',
                                    'ip_pool_id' => $vtepIpPool->id
                                ]
                            ]
                        ]
                    ],
                    'tags' => [
                        [
                            'scope' => config('defaults.tag.scope'),
                            'tag' => $hostGroup->vpc->id
                        ]
                    ]
                ]
            ]
        );
    }

    private function getTransportNodeProfiles(AvailabilityZone $availabilityZone): ?\stdClass
    {
        return json_decode(
            $availabilityZone->nsxService()
                ->get('/api/v1/transport-node-profiles')
                ->getBody()
                ->getContents()
        );
    }

    private function getNetworkSwitchDetails(AvailabilityZone $availabilityZone, Vpc $vpc): ?\stdClass
    {
        return json_decode(
            $availabilityZone->kingpinService()
                ->get('/api/v2/vpc/' . $vpc->id . '/network/switch')
                ->getBody()
                ->getContents()
        );
    }

    private function getTransportZones(AvailabilityZone $availabilityZone): ?\stdClass
    {
        return json_decode(
            $availabilityZone->nsxService()
                ->get('/api/v1/search/query?query=resource_type:TransportZone%20AND%20tags.scope:ukfast%20AND%20tags.tag:default-overlay-tz')
                ->getBody()
                ->getContents()
        );
    }

    private function getUplinkHostSwitchProfiles(AvailabilityZone $availabilityZone): ?\stdClass
    {
        return json_decode(
            $availabilityZone->nsxService()
                ->get('/api/v1/search/query?query=resource_type:UplinkHostSwitchProfile%20AND%20tags.scope:ukfast%20AND%20tags.tag:default-uplink-profile')
                ->getBody()
                ->getContents()
        );
    }

    private function getVtepIpPools(AvailabilityZone $availabilityZone): ?\stdClass
    {
        return json_decode(
            $availabilityZone->nsxService()
                ->get('/api/v1/search/query?query=resource_type:IpPool%20AND%20tags.scope:ukfast%20AND%20tags.tag:default-vtep-ip-pool')
                ->getBody()
                ->getContents()
        );
    }
}
