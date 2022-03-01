<?php

namespace App\Jobs\Vpc\Defaults;

use App\Jobs\Job;
use App\Jobs\Router\ConfigureRouterDefaults;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use App\Traits\V2\LoggableModelJob;

class ConfigureVpcDefaults extends Job
{
    use LoggableModelJob;

    private $model;
    private AvailabilityZone $availabilityZone;

    public $tries = 1;

    public function __construct(Vpc $vpc, AvailabilityZone $availabilityZone)
    {
        $this->model = $vpc;
        $this->availabilityZone = $availabilityZone;
    }

    public function handle()
    {
        // Create a new router
        $router = app()->make(Router::class);
        $router->vpc()->associate($this->model);
        $router->availabilityZone()->associate($this->availabilityZone);
        $router->syncSave();

        dispatch((new AwaitRouterSync($router))->chain([
            new CreateNetwork($router),
            new ConfigureRouterDefaults($router),
        ]));
    }
}
