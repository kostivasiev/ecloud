<?php

namespace App\Jobs\Instance\Undeploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class DeleteNics extends Job
{
    use Batchable;

    private $instance;

    public function __construct(Instance $instance)
    {
        $this->instance = $instance;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->instance->id]);

        $this->instance->nics()->each(function ($nic) {
            $nic->delete();
        });

        Log::info(get_class($this) . ' : Finished', ['id' => $this->instance->id]);
    }
}
