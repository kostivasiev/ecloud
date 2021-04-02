<?php

namespace App\Jobs\Router;

use App\Jobs\Job;
use App\Models\V2\Router;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class Undeploy extends Job
{
    use Batchable;

    private $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->router->id]);

        # Delete the router
        $this->router->availabilityZone->nsxService()->delete('policy/api/v1/infra/tier-1s/' . $this->router->id);

        Log::info(get_class($this) . ' : Finished', ['id' => $this->router->id]);
    }
}
