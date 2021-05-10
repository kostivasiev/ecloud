<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Support\Sync;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CheckNetworkAvailable extends Job
{
    use Batchable, LoggableModelJob;

    public $tries = 30;
    public $backoff = 5;

    private $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    public function handle()
    {
        $network = Network::findOrFail($this->model->deploy_data['network_id']);

        if ($network->sync->status == Sync::STATUS_FAILED) {
            $this->fail(new \Exception("Network '" . $network->id . "' in failed sync state"));
            return;
        }

        if ($network->sync->status == Sync::STATUS_INPROGRESS) {
            Log::warning('Network not in sync, retrying in ' . $this->backoff . ' seconds', [
                'id' => $this->model->id,
                'network' => $network->id
            ]);
            $this->release($this->backoff);
            return;
        }
    }
}
