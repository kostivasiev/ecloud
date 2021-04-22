<?php

namespace App\Jobs\Vpc\Defaults;

use App\Jobs\Job;
use App\Models\V2\Network;
use App\Models\V2\Router;
use Illuminate\Support\Facades\Log;

class CreateNetwork extends Job
{
    public $tries = 60;
    public $backoff = 10;

    private $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->router->id]);

        // Create a new network
        $network = app()->make(Network::class);
        $network->router()->associate($this->router);
        $network->save();

        Log::info(get_class($this) . ' : Finished', ['id' => $this->router->id]);
    }
}
