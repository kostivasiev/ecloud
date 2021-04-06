<?php

namespace App\Jobs\Router;

use App\Jobs\Job;
use App\Models\V2\Router;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class UndeployCheck extends Job
{
    use Batchable;

    // Wait up to 30 minutes
    public $tries = 360;
    public $backoff = 5;

    private Router $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->router->id]);

        $response = $this->router->availabilityZone->nsxService()->get(
            'policy/api/v1/infra/tier-1s/?include_mark_for_delete_objects=true'
        );
        $response = json_decode($response->getBody()->getContents());
        foreach ($response->results as $result) {
            if ($this->router->id === $result->id) {
                Log::info(
                    'Waiting for ' . $this->router->id . ' being deleted, retrying in ' . $this->backoff . ' seconds'
                );
                return $this->release($this->backoff);
            }
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->router->id]);
    }
}
