<?php

namespace App\Jobs\Vpc\Defaults;

use App\Jobs\Job;
use App\Jobs\Router\Defaults\ConfigureRouterDefaults;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use App\Models\V2\Router;
use App\Support\Sync;
use App\Models\V2\Vpc;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class ConfigureVpcDefaults extends Job
{
    private $vpc;

    public $tries = 1;

    public function __construct(Vpc $vpc)
    {
        $this->vpc = $vpc;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['resource_id' => $this->vpc->id]);

        $availabilityZone = $this->vpc->region()->first()->availabilityZones()->first();

        // Create a new router
        $router = app()->make(Router::class);
        $router->vpc()->associate($this->vpc);
        $router->availabilityZone()->associate($availabilityZone);
        $router->save();

        dispatch((new AwaitRouterSync($router))->chain([
            new CreateNetwork($router),
            new ConfigureRouterDefaults($router),
        ]));

        Log::info(get_class($this) . ' : Finished', ['resource_id' => $this->vpc->id]);
    }
}
