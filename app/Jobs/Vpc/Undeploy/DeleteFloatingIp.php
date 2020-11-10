<?php

namespace App\Jobs\Vpc\Undeploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Illuminate\Support\Facades\Log;

class DeleteFloatingIp extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['data' => $this->data]);

        $vpc = Vpc::withTrashed()->findOrFail($this->data['vpc_id']);
        $logMessage = 'Delete Routers for VPC ' . $vpc->getKey() . ': ';

        $vpc->floatingIps()->each(function ($floatingIp) {
            $floatingIp->delete();
        });

        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
