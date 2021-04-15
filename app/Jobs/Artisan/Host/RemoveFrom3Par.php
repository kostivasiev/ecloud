<?php

namespace App\Jobs\Artisan\Host;

use App\Jobs\Job;
use App\Models\V2\Host;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class RemoveFrom3Par extends Job
{
    use Batchable;

    private $model;

    public function __construct(Host $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $host = $this->model;
        $availabilityZone = $host->hostGroup->availabilityZone;

        try {
            $availabilityZone->artisanService()->delete(
                '/api/v2/san/' . $availabilityZone->san_name . '/host/' . $host->id
            );
        } catch (RequestException $exception) {
            Log::error(get_class($this) . ' : Failed', [
                'id' => $host->id,
                'status_code' => $exception->getCode(),
                'content' => $exception->getResponse()->getBody()->getContents()
            ]);
            if ($exception->getCode() != 404) {
                throw $exception;
            }
            Log::warning(get_class($this) . ' : Host ' . $host->id . ' was not removed from 3Par.');
            return;
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}
