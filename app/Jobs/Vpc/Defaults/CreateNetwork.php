<?php

namespace App\Jobs\Vpc\Defaults;

use App\Jobs\Job;
use App\Models\V2\Network;
use App\Models\V2\Router;
use App\Traits\V2\JobModel;

class CreateNetwork extends Job
{
    use JobModel;

    public $tries = 60;
    public $backoff = 10;

    private $model;

    public function __construct(Router $router)
    {
        $this->model = $router;
    }

    public function handle()
    {
        // Create a new network
        $network = app()->make(Network::class);
        $network->router()->associate($this->model);
        $network->save();
    }
}
