<?php

namespace App\Jobs\Nsx\HostGroup;

use App\Jobs\Job;
use App\Models\V2\HostGroup;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class DeleteTransportNodeProfile extends Job
{
    use Batchable, LoggableModelJob;

    public $model;

    public function __construct(HostGroup $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        $hostGroup = $this->model;
        // Compute Collection Item
        try {
            $response = $hostGroup->availabilityZone->nsxService()
                ->get('/api/v1/fabric/compute-collections?origin_type=VC_Cluster&display_name=' . $hostGroup->id);
            $response = json_decode($response->getBody()->getContents());
        } catch (RequestException $exception) {
            if ($exception->getCode() !== 404) {
                $this->fail($exception);
                return;
            }
            Log::warning(
                get_class($this) . ' : Compute Collection for HostGroup ' .
                $hostGroup->id . ' could not be retrieved, skipping.'
            );
            return;
        }
        $computeItem = collect($response->results)->first();
        if (empty($computeItem)) {
            Log::warning('Compute Item for HostGroup ' . $hostGroup->id . ' not found, skipping');
            return;
        }

        try {
            $response = $this->model->availabilityZone->nsxService()
                ->get('/api/v1/transport-node-collections?compute_collection_id=' . $computeItem->external_id);
            $response = json_decode($response->getBody()->getContents());
        } catch (RequestException $exception) {
            if ($exception->getCode() !== 404) {
                $this->fail($exception);
                return;
            }
            Log::warning(
                get_class($this) . ' : TransportNode Collection for HostGroup ' .
                $hostGroup->id . ' could not be retrieved, skipping.'
            );
            return;
        }
        $transportNodeItem = collect($response->results)->first();

        // Check there are items to detach
        if (empty($transportNodeItem)) {
            Log::warning(
                get_class($this) . ' : No Transport Node Collection Items found for ' .
                $hostGroup->id . ', skipping'
            );
            return;
        }

        // Detach the node
        try {
            $response = $this->model->availabilityZone->nsxService()->delete(
                '/api/v1/transport-node-collections/' . $transportNodeItem->id
            );
        } catch (RequestException $exception) {
            if ($exception->getCode() !== 404) {
                $this->fail($exception);
                return;
            }
            Log::warning(
                get_class($this) . ' : Failed to detach transport node profile for Host Group ' .
                $hostGroup->id . ', skipping'
            );
            return;
        }

        // Once the Profile is Detached it can be deleted
        try {
            $this->model->availabilityZone->nsxService()->delete(
                '/api/v1/transport-node-profiles/' . $transportNodeItem->id
            );
        } catch (RequestException $exception) {
            if ($exception->getCode() !== 404) {
                $this->fail($exception);
                return;
            }
            Log::warning(
                get_class($this) . ' : Failed to delete transport node profile for Host Group ' .
                $hostGroup->id . ', skipping.'
            );
            return;
        }
    }
}
