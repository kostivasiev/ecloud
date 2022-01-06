<?php

namespace App\Jobs\Kingpin\HostGroup;

use App\Jobs\TaskJob;

class DeleteCluster extends TaskJob
{
    public function handle()
    {
        $hostGroup = $this->task->resource;

        try {
            $hostGroup->availabilityZone->kingpinService()->delete(
                '/api/v2/vpc/' . $hostGroup->vpc->id . '/hostgroup/' . $hostGroup->id
            );
        } catch (\Exception $exception) {
            if ($exception->getCode() !== 404) {
                throw $exception;
            }
            $this->warning('Host group cluster was not found, skipping');
            return;
        }
    }
}
