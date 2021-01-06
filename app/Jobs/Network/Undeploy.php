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

        $network = Network::findOrFail($this->data['network_id']);

        $network->router->availabilityZone->nsxService()->delete(
            'policy/api/v1/infra/tier-1s/' . $network->router->id . '/segments/' . $network->id
        );

        // TODO :- Work out how to monitor NSX to confirm the delete has taken place before deleting the model.
        // Can't wait in this job so make a chain in the controller OR dispatch another job from in here?
        $network->setSyncCompleted();
        $network->delete();

        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
