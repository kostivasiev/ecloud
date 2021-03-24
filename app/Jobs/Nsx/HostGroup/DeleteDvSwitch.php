<?php

namespace App\Jobs\Nsx\HostGroup;

use App\Jobs\Job;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\HostGroup;
use App\Models\V2\Vpc;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\Log;

class DeleteDvSwitch extends Job
{
    private $model;

    public function __construct(HostGroup $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $message = null;
        $hostGroup = $this->model;
        $networkSwitch = json_decode(
            $hostGroup->availabilityZone->kingpinService()
                ->get('/api/v2/vpc/' . $hostGroup->vpc->id . '/network/switch')
                ->getBody()
                ->getContents()
        );
        if (!$networkSwitch) {
            $this->fail(new \Exception('Failed to get NetworkSwitch'));
            return false;
        }

        try {
            $response = $hostGroup->availabilityZone->nsxService()->delete(
                '/api/v2/vpc/' . $hostGroup->vpc->id . '/network/switch/' . $networkSwitch->uuid
            );
        } catch (ClientException|ServerException $e) {
            $response = $e->getResponse();
            $message = $e->getMessage();
        }
        if ($response->getStatusCode() !== 200) {
            $message = get_class($this) . ': ' . ($message ?? 'Failed to delete switch `' . $networkSwitch->uuid . '`');
            Log::debug($message);
            $this->fail(new \Exception($message));
            return false;
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }

    public function failed($exception)
    {
        $this->model->setSyncFailureReason($exception->getMessage());
    }
}
