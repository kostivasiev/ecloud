<?php

namespace App\Jobs\Kingpin\HostGroup;

use App\Jobs\TaskJob;
use GuzzleHttp\Exception\RequestException;

class CreateCluster extends TaskJob
{
    public function handle()
    {
        $hostGroup = $this->task->resource;

        // Check if it already exists and if do skip creating it
        try {
            $response = $hostGroup->availabilityZone->kingpinService()
                ->get('/api/v2/vpc/' . $hostGroup->vpc->id . '/hostgroup/' . $hostGroup->id);
            if ($response->getStatusCode() == 200) {
                $this->debug('HostGroup already exists, nothing to do.');
                return true;
            }
        } catch (RequestException $exception) {
            if ($exception->getCode() != 404) {
                throw $exception;
            }
        }

        $hostGroup->availabilityZone->kingpinService()->post(
            '/api/v2/vpc/' . $hostGroup->vpc->id . '/hostgroup',
            [
                'json' => [
                    'hostGroupId' => $hostGroup->id,
                    'shared' => false,
                ],
            ]
        );
    }
}
