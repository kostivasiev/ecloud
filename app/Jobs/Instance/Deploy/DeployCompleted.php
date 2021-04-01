<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class DeployCompleted extends Job
{
    use Batchable;

    private $instance;

    public function __construct(Instance $instance)
    {
        $this->instance = $instance;
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->instance->id]);

        $this->instance->deployed = true;
        //this->instance->deploy_data = '';
        $this->instance->saveQuietly();

        Log::info(get_class($this) . ' : Finished', ['id' => $this->instance->id]);
    }
}
