<?php

namespace App\Jobs\Nsx\HostGroup;

use App\Jobs\Job;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\HostGroup;
use Illuminate\Support\Facades\Log;

class PrepareCluster extends Job
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

        $transportNodeCollections = $this->getTransportNodeCollections($hostGroup->availabilityZone);
        if ($transportNodeCollections === null) {
            $this->fail(new \Exception('Failed to get TransportNodeCollections'));
            return false;
        }

        $transportNodeCollectionDisplayName = $this->model->id . '-tnc';
        $exists = collect($transportNodeCollections['results'])->filter(function ($result) use (
            $transportNodeCollectionDisplayName
        ) {
            return ($result['display_name'] === $transportNodeCollectionDisplayName);
        })->count();
        if ($exists) {
            Log::info(get_class($this) . ' : Skipped', [
                'id' => $this->model->id,
            ]);
            return true;
        }

        $transportNodeProfileId = $this->getTransportNodeProfileId($hostGroup->availabilityZone, $hostGroup);
        if (!$transportNodeProfileId) {
            $this->fail(new \Exception('Failed to get TransportNodeProfileId'));
            return false;
        }

        $computeCollectionId = $this->getHostGroupComputeCollectionId($hostGroup->availabilityZone, $hostGroup);
        if (!$computeCollectionId) {
            $this->fail(new \Exception('Failed to get ComputeCollectionId'));
            return false;
        }

        $response = $hostGroup->availabilityZone->nsxService()->post(
            '/api/v1/transport-node-collections',
            [
                'json' => [
                    'resource_type' => 'TransportNodeCollection',
                    'display_name' => $transportNodeCollectionDisplayName,
                    'description' => 'API created Transport Node Collection',
                    'compute_collection_id' => $computeCollectionId,
                    'transport_node_profile_id' => $transportNodeProfileId,
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

    private function getTransportNodeCollections(AvailabilityZone $availabilityZone): ?array
    {
        $response = $availabilityZone->nsxService()
            ->get('/api/v1/transport-node-collections');
        if (!$response || $response->getStatusCode() !== 200) {
            return null;
        }
        $json = json_decode($response->getBody()->getContents(), true);
        return (!$json) ? null : $json;
    }

    private function getTransportNodeProfileId(AvailabilityZone $availabilityZone, HostGroup $hostGroup): ?string
    {
        $response = $availabilityZone->nsxService()
            ->get('/api/v1/search/query?query=resource_type:TransportNodeProfile%20AND%20display_name:tnp-' . $hostGroup->id);
        if (!$response || $response->getStatusCode() !== 200) {
            return null;
        }
        $json = json_decode($response->getBody()->getContents(), true);
        if (!isset($json['results']) || !count($json['results'])) {
            return null;
        }
        $firstResult = collect($json['results'])->first();
        return (isset($firstResult['external_id'])) ? $firstResult['external_id'] : null;
    }

    private function getHostGroupComputeCollectionId(AvailabilityZone $availabilityZone, HostGroup $hostGroup): ?string
    {
        $response = $availabilityZone->nsxService()
            ->get('/api/v1/fabric/compute-collections?origin_type=VC_Cluster&display_name=' . $hostGroup->id);
        if (!$response || $response->getStatusCode() !== 200) {
            return null;
        }
        $json = json_decode($response->getBody()->getContents(), true);
        if (!isset($json['results']) || !count($json['results'])) {
            return null;
        }
        $firstResult = collect($json['results'])->first();
        return (isset($firstResult['external_id'])) ? $firstResult['external_id'] : null;
    }

    public function failed($exception)
    {
        $this->model->setSyncFailureReason($exception->getMessage());
    }
}
