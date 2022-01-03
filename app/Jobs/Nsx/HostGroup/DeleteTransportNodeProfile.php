<?php

namespace App\Jobs\Nsx\HostGroup;

use App\Jobs\TaskJob;

class DeleteTransportNodeProfile extends TaskJob
{
    public function handle()
    {
        $hostGroup = $this->task->resource;

        $transportNodeProfileResponse = json_decode(
            $hostGroup->availabilityZone->nsxService()
                ->get('/api/v1/search/query?query=resource_type:TransportNodeProfile%20AND%20display_name:tnp-' . $hostGroup->id)
                ->getBody()
                ->getContents()
        );

        $transportNodeItem = collect($transportNodeProfileResponse->results)->first();
        if (!empty($transportNodeItem)) {
            $transportNodeCollectionResponse = json_decode(
                $hostGroup->availabilityZone->nsxService()
                    ->get('/api/v1/search/query?query=resource_type:TransportNodeCollection%20AND%20transport_node_profile_id:' . $transportNodeItem->id)
                    ->getBody()
                    ->getContents()
            );

            $transportNodeCollectionItem = collect($transportNodeCollectionResponse->results)->first();
            if (!empty($transportNodeCollectionItem)) {
                $hostGroup->availabilityZone->nsxService()->delete(
                    '/api/v1/transport-node-collections/' . $transportNodeCollectionItem->id
                );
            }

            // Once the Profile is Detached it can be deleted
            $hostGroup->availabilityZone->nsxService()->delete(
                '/api/v1/transport-node-profiles/' . $transportNodeItem->id
            );
        }
    }
}
