<?php

namespace App\Jobs\Nsx\HostGroup;

use App\Jobs\Job;
use App\Models\V2\HostGroup;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class DeleteTransportNodeProfile extends Job
{
    use Batchable;

    public $model;

    public function __construct(HostGroup $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $hostGroup = $this->model;

        // Compute Collection Item
        try {
            $response = $hostGroup->availabilityZone->nsxService()
                ->get('/api/v1/fabric/compute-collections?origin_type=VC_Cluster&display_name=' . $hostGroup->id);
            $response = json_decode($response->getBody()->getContents());
        } catch (RequestException $exception) {
            if ($exception->getCode() !== 404) {
                $this->fail($exception);
            }
            Log::warning(
                get_class($this) . ' : Compute Collection for HostGroup ' .
                $hostGroup->id . ' could not be retrieved, skipping.'
            );
            return false;
        }
        $computeItem = collect($response->results)->first();
        if (empty($computeItem)) {
            Log::warning('Compute Item for HostGroup ' . $hostGroup->id . ' not found, skipping');
            return false;
        }

        try {
            $response = $this->model->availabilityZone->nsxService()
                ->get('/api/v1/transport-node-collections?compute_collection_id=' . $computeItem->external_id);
            $response = json_decode($response->getBody()->getContents());
        } catch (RequestException $exception) {
            if ($exception->getCode() !== 404) {
                $this->fail($exception);
            }
            Log::warning(
                get_class($this) . ' : TransportNode Collection for HostGroup ' .
                $hostGroup->id . ' could not be retrieved, skipping.'
            );
            return false;
        }
        $transportNodeItem = collect($response->results)->first();

        // Detach the node
        try {
            $response = $this->model->availabilityZone->nsxService()->delete(
                '/api/v1/transport-node-collections/' . $transportNodeItem->id
            );
        } catch (RequestException $exception) {
            if ($exception->getCode() !== 404) {
                $this->fail($exception);
            }
            Log::warning(
                get_class($this) . ' : Failed to detach transport node profile for Host Group ' .
                $hostGroup->id . ', skipping'
            );
            return false;
        }

        // Once the Profile is Detached it can be deleted
        try {
            $response = $this->model->availabilityZone->nsxService()->delete(
                '/api/v1/transport-node-profiles/' . $transportNodeItem->id
            );
        } catch (RequestException $exception) {
            if ($exception->getCode() != 404) {
                throw $exception;
            }
            Log::warning(
                get_class($this) . ' : Failed to delete transport node profile for Host Group ' .
                $hostGroup->id . ', skipping.'
            );
            return false;
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}
