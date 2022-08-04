<?php

namespace App\Jobs\Nsx\HostGroup;

use App\Jobs\TaskJob;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\HostGroup;

class PrepareCluster extends TaskJob
{
    public function handle()
    {
        $hostGroup = $this->task->resource;

        $transportNodeCollections = $this->getTransportNodeCollections($hostGroup->availabilityZone);
        if (!$transportNodeCollections || !count($transportNodeCollections->results)) {
            $this->fail(new \Exception('Failed to get TransportNodeCollections'));
            return false;
        }

        $transportNodeCollectionDisplayName = 'tnc-' . $hostGroup->id;
        $exists = collect($transportNodeCollections->results)->filter(function ($result) use (
            $transportNodeCollectionDisplayName
        ) {
            return ($result->display_name === $transportNodeCollectionDisplayName);
        })->count();
        if ($exists) {
            $this->debug('Already exists, skipping');
            return true;
        }

        $transportNodeProfiles = $this->getTransportNodeProfiles($hostGroup->availabilityZone, $hostGroup);
        if (!$transportNodeProfiles || !count($transportNodeProfiles->results)) {
            $this->fail(new \Exception('Failed to get TransportNodeProfiles'));
            return false;
        }
        $transportNodeProfile = collect($transportNodeProfiles->results)->first();

        $computeCollections = $this->getHostGroupComputeCollections($hostGroup->availabilityZone, $hostGroup);
        if (!$computeCollections || !count($computeCollections->results)) {
            $this->fail(new \Exception('Failed to get ComputeCollections'));
            return false;
        }
        $computeCollection = collect($computeCollections->results)->first();

        $hostGroup->availabilityZone->nsxService()->post(
            '/api/v1/transport-node-collections',
            [
                'json' => [
                    'resource_type' => 'TransportNodeCollection',
                    'display_name' => $transportNodeCollectionDisplayName,
                    'description' => 'API created Transport Node Collection',
                    'compute_collection_id' => $computeCollection->external_id,
                    'transport_node_profile_id' => $transportNodeProfile->id,
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

    private function getTransportNodeCollections(AvailabilityZone $availabilityZone): ?\stdClass
    {
        return json_decode(
            $availabilityZone->nsxService()
                ->get('/api/v1/transport-node-collections')
                ->getBody()
                ->getContents()
        );
    }

    private function getTransportNodeProfiles(AvailabilityZone $availabilityZone, HostGroup $hostGroup): ?\stdClass
    {
        return json_decode(
            $availabilityZone->nsxService()
                ->get('/api/v1/search/query?query=resource_type:TransportNodeProfile%20AND%20display_name:tnp-' . $hostGroup->id)
                ->getBody()
                ->getContents()
        );
    }

    private function getHostGroupComputeCollections(
        AvailabilityZone $availabilityZone,
        HostGroup $hostGroup
    ): ?\stdClass {
        return json_decode(
            $availabilityZone->nsxService()
                ->get('/api/v1/fabric/compute-collections?origin_type=VC_Cluster&display_name=' . $hostGroup->id)
                ->getBody()
                ->getContents()
        );
    }
}
