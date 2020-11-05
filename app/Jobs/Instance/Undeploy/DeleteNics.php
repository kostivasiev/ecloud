<?php

namespace App\Jobs\Instance\Undeploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use Illuminate\Support\Facades\Log;

class DeleteNics extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['data' => $this->data]);

        $instance = Instance::withTrashed()->findOrFail($this->data['instance_id']);
        $logMessage = 'DeleteNics for instance ' . $instance->getKey() . ': ';

        $instance->nics()->each(function ($nic) {
            $nic->delete();
        });

        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
