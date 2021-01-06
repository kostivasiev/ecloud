<?php

namespace App\Jobs\Network;

use App\Jobs\Job;
use App\Models\V2\Network;
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

        $model = Network::findOrFail($this->data['network_id']);

        $response = $model->router->availabilityZone->nsxService()->get(
            'policy/api/v1/infra/tier-1s/' . $model->router->id . '/segments/?include_mark_for_delete_objects=true'
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
