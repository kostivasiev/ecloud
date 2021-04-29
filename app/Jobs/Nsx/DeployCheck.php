<?php

namespace App\Jobs\Nsx;

use App\Jobs\Job;
use App\Models\V2\AvailabilityZone;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class DeployCheck extends Job
{
    use Batchable;

    const RETRY_DELAY = 5;

    public $tries = 500;

    protected $model;

    protected AvailabilityZone $availabilityZone;

    protected string $intentPath;

    public function __construct($model, AvailabilityZone $availabilityZone, $resourcePath)
    {
        $this->model = $model;
        $this->availabilityZone = $availabilityZone;
        $this->intentPath = $resourcePath . $this->model->id;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        if (empty($this->availabilityZone)) {
            $this->fail(new \Exception('Failed to check deploy status: Availability zone is undefined'));
            return;
        }

        if (empty($this->intentPath)) {
            $this->fail(new \Exception('Failed to check deploy status: Resource path is undefined'));
            return;
        }

        $response = $this->availabilityZone->nsxService()->get(
            'policy/api/v1/infra/realized-state/status?intent_path=' . $this->intentPath
        );

        $response = json_decode($response->getBody()->getContents());
        if ($response->publish_status !== 'REALIZED') {
            $this->release(static::RETRY_DELAY);
            Log::info(
                'Waiting for ' . $this->model->id . ' being deployed, retrying in ' . static::RETRY_DELAY . ' seconds'
            );
            return;
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}
