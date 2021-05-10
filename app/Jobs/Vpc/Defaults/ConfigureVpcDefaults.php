<?php

namespace App\Jobs\Vpc\Defaults;

use App\Jobs\Job;
use App\Jobs\Router\Defaults\ConfigureRouterDefaults;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use App\Traits\V2\LoggableModelJob;

class ConfigureVpcDefaults extends Job
{
    use LoggableModelJob;

    private $model;

    public $tries = 1;

    public function __construct(Vpc $vpc)
    {
        $this->model = $vpc;
    }

    public function handle()
    {
        $availabilityZone = $this->model->region()->first()->availabilityZones()->first();

        // Create a new router
        $router = app()->make(Router::class);
        $router->vpc()->associate($this->model);
        $router->availabilityZone()->associate($availabilityZone);
        $router->save();

        dispatch((new AwaitRouterSync($router))->chain([
            new CreateNetwork($router),
            new ConfigureRouterDefaults($router),
        ]));
    }
}
