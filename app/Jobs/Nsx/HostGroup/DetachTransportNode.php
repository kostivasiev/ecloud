<?php

namespace App\Jobs\Nsx\HostGroup;

use App\Jobs\Job;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\HostGroup;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;

class DetachTransportNode extends Job
{
    public $model;

    public function __construct(HostGroup $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $hostGroup = $this->model;
        $message = null;

        // Compute Collection Item
        $response = $hostGroup->availabilityZone->nsxService()
            ->get('/api/v1/fabric/compute-collections?origin_type=VC_Cluster&display_name=' . $hostGroup->id);
        if (!$response || $response->getStatusCode() !== 200) {
            $this->fail(new \Exception('Failed to get ComputeCollection item'));
            return false;
        }
        $computeItem = collect(json_decode($response->getBody()->getContents())->results)->first();

        // Transport Node Item
        $response = $this->model->availabilityZone->nsxService()
            ->get('/api/v1/transport-node-collections?compute_collection_id=' . $computeItem->external_id);
        if (!$response || $response->getStatusCode() !== 200) {
            $this->fail(new \Exception('Failed to get TransportNode item'));
            return false;
        }
        $transportNodeItem = collect(json_decode($response->getBody()->getContents())->results)->first();

        // Detach the node
        try {
            $response = $this->model->availabilityZone->nsxService()->delete(
                '/api/v1/transport-node-profiles/' . $transportNodeItem->id
            );
        } catch (ClientException|ServerException $e) {
            $response = $e->getResponse();
            $message = $e->getMessage();
        }
        if ($response->getStatusCode() !== 200) {
            $message = $message ?? 'Failed to delete transport node profile for Host Group ' . $hostGroup->id;
            Log::debug($message);
            $this->fail(new \Exception($message));
            return false;
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }

    public function failed($exception)
    {
        $this->model->setSyncFailureReason($exception->getMessage());
    }
}
