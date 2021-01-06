<?php

namespace App\Jobs\Network;

use App\Jobs\Job;
use App\Models\V2\Network;
use Illuminate\Support\Facades\Log;

class Undeploy extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['data' => $this->data]);

        $model = Network::findOrFail($this->data['network_id']);

        $model->router->availabilityZone->nsxService()->delete(
            'policy/api/v1/infra/tier-1s/' . $model->router->id . '/segments/' . $model->id
        );

        dispatch(new UndeployCheck([
            'network_id' => $model->id,
        ]));

        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
