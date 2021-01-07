<?php

namespace App\Jobs\Nsx\Dhcp;

use App\Jobs\Job;
use App\Models\V2\Dhcp;
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

        $model = Dhcp::findOrFail($this->data['id']);

        $model->availabilityZone->nsxService()->delete(
            '/policy/api/v1/infra/dhcp-server-configs/' . $model->id
        );

        dispatch(new UndeployCheck([
            'id' => $model->id,
        ]));

        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
