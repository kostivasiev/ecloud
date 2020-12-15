<?php

namespace App\Jobs\Router\Undeploy;

use App\Jobs\Job;
use App\Models\V2\Router;
use Illuminate\Support\Facades\Log;

class DeleteNetworks extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['data' => $this->data]);
        $router = Router::withTrashed()->findOrFail($this->data['router_id']);
        $router->networks()->each(function ($network) {
            $network->delete();
        });
        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
