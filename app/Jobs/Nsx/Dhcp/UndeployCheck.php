<?php

namespace App\Jobs\Nsx\Dhcp;

use App\Jobs\Job;
use App\Models\V2\Dhcp;
use Illuminate\Support\Facades\Log;

class UndeployCheck extends Job
{
    const RETRY_DELAY = 5;

    public $tries = 500;

    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['data' => $this->data]);

        $model = Dhcp::findOrFail($this->data['id']);

        $response = $model->availabilityZone->nsxService()->get(
            '/policy/api/v1/infra/dhcp-server-configs/?include_mark_for_delete_objects=true'
        );
        $response = json_decode($response->getBody()->getContents());
        foreach ($response->results as $segment) {
            if ($model->id === $segment->id) {
                $this->release(static::RETRY_DELAY);
                Log::info(
                    'Waiting for segment ' . $model->id . ' being deleted, retrying in ' . static::RETRY_DELAY . ' seconds'
                );
                return;
            }
        }

        $model->setSyncCompleted();
        $model->delete();

        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
