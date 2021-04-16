<?php
namespace App\Jobs\Kingpin\Host;

use App\Jobs\Job;
use App\Models\V2\Host;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CheckExists extends Job
{
    use Batchable;

    private Host $host;

    public function __construct(Host $host)
    {
        $this->host = $host;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->host->id]);

        $availabilityZone = $this->host->hostGroup->availabilityZone;
        try {
            $response = $availabilityZone->kingpinService()->get(
                '/api/v2/san/' . $availabilityZone->san_name . '/host/' . $this->host->id
            );
        } catch (RequestException $exception) {// handle 40x/50x response if host not found
            if ($exception->getCode() != 404) {
                $this->fail($exception);
                throw $exception;
            }
            $message = get_class($this) . ' : Host does not exist, skipping.';
            Log::warning($message);
            $this->batch()->cancel();
            return;
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->host->id]);
    }
}