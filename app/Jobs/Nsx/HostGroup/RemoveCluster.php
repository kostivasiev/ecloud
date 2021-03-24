<?php

namespace App\Jobs\Nsx\HostGroup;

use App\Jobs\Job;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\HostGroup;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\Log;

class RemoveCluster extends Job
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

        $transportNodeCollections = $this->getTransportNodeCollections();
        if (!$transportNodeCollections || !count($transportNodeCollections->results)) {
            $this->fail(new \Exception('Failed to get TransportNodeCollections'));
            return false;
        }

        $transportNodeCollectionDisplayName = $this->model->id . '-tnc';
        $exists = collect($transportNodeCollections->results)->filter(function ($result) use (
            $transportNodeCollectionDisplayName
        ) {
            return ($result->display_name === $transportNodeCollectionDisplayName);
        })->count();
        if ($exists) {
            Log::info(get_class($this) . ' : Skipped', [
                'id' => $this->model->id,
            ]);
            return true;
        }

        $transportNodeProfiles = $this->getTransportNodeProfiles();
        if (!$transportNodeProfiles || !count($transportNodeProfiles->results)) {
            $this->fail(new \Exception('Failed to get TransportNodeProfiles'));
            return false;
        }
        $transportNodeProfile = collect($transportNodeProfiles->results)->first();

        $computeCollections = $this->getHostGroupComputeCollections();
        if (!$computeCollections || !count($computeCollections->results)) {
            $this->fail(new \Exception('Failed to get ComputeCollections'));
            return false;
        }
        $computeCollection = collect($computeCollections->results)->first();

        ### Find the nodes that need to be removed
        $response = $hostGroup->availabilityZone->nsxService()->get('/api/v1/transport-node-collections');
        $response = json_decode($response->getBody()->getContent());
        collect($response->results)->each(
            function ($node) use ($hostGroup, $transportNodeProfile, $computeCollection) {
                if ($node->transport_node_profile_id === $transportNodeProfile->id &&
                    $node->compute_collection_id === $computeCollection->id) {
                    try {
                        $response = $hostGroup->availabilityZone->nsxService()->delete(
                            '/api/v1/transport-node-collections/' . $node->id
                        );
                    } catch (ServerException | ClientException $e) {
                        $response = $e->getResponse();
                    }
                    if ($response->getStatusCode() !== 200) {
                        $message = get_class($this) . ': Failed to delete transport node collection item `' . $node->id .
                            '` for HostGroup ' . $hostGroup->id;
                        Log::debug($message, [
                            'host_group_id' => $hostGroup->id,
                            'transport_node_id' => $node->id,
                        ]);
                        $this->fail(new \Exception($message));
                        return false;
                    }
                    return true;
                }
            }
        );

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }

    private function getTransportNodeCollections(): ?\stdClass
    {
        return json_decode(
            $this->model->availabilityZone->nsxService()
                ->get('/api/v1/transport-node-collections')
                ->getBody()
                ->getContents()
        );
    }

    private function getTransportNodeProfiles(): ?\stdClass
    {
        return json_decode(
            $this->model->availabilityZone->nsxService()
                ->get('/api/v1/search/query?query=resource_type:TransportNodeProfile%20AND%20display_name:tnp-' . $this->model->id)
                ->getBody()
                ->getContents()
        );
    }

    private function getHostGroupComputeCollections(): ?\stdClass
    {
        return json_decode(
            $this->model->availabilityZone->nsxService()
                ->get('/api/v1/fabric/compute-collections?origin_type=VC_Cluster&display_name=' . $this->model->id)
                ->getBody()
                ->getContents()
        );
    }

    public function failed($exception)
    {
        $message = $exception->getMessage();
        if ($exception instanceof RequestException && $exception->hasResponse()) {
            $json = json_decode($exception->getResponse()->getBody()->getContents());
            Log::error('Request Exception', [
                'response_json' => $json,
                'exception' => $exception,
            ]);
        }
        $this->model->setSyncFailureReason($message);
    }
}
