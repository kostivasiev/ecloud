<?php

namespace App\Jobs\Nsx\HostGroup;

use App\Jobs\Job;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\HostGroup;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\Log;

class DeleteTransportNode extends Job
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

        // Get transport node profiles
        $transportNodeProfiles = $this->getTransportNodeProfiles();
        if (!$transportNodeProfiles) {
            $this->fail(new \Exception('Failed to get TransportNodeProfiles'));
            return false;
        }

        $transportNodeProfileDisplayName = $hostGroup->id . '-tnp';
        $transportNodeProfile = collect($transportNodeProfiles->results)->filter(function ($result) use (
            $transportNodeProfileDisplayName
        ) {
            return ($result->display_name === $transportNodeProfileDisplayName);
        });
        if ($transportNodeProfile->count() <= 0) {
            // transport node not found, so skip
            Log::info(get_class($this) . ' : Skipped', [
                'id' => $this->model->id,
            ]);
            return true;
        }

        try {
            $response = $hostGroup->availabilityZone->nsxService()->delete(
                '/api/v1/transport-node-profiles/' . $transportNodeProfile->id
            );
        } catch (ClientException|ServerException $e) {
            $response = $e->getResponse();
            $message = $e->getMessage();
        }
        if ($response->getStatusCode() !== 200) {
            $message = get_class($this) . ': ' . ($message ?? 'Failed to delete transport node profile `' .
                    $transportNodeProfile->id . '`');
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

    public function getTransportNodeProfiles(): ?\stdClass
    {
        return json_decode(
            $this->model->availabilityZone->nsxService()
                ->get('/api/v1/transport-node-profiles')
                ->getBody()
                ->getContents()
        );
    }
}
