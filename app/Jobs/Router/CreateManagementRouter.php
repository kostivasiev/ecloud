<?php
namespace App\Jobs\Router;

use App\Jobs\TaskJob;
use App\Models\V2\Router;
use App\Traits\V2\TaskJobs\AwaitResources;

class CreateManagementRouter extends TaskJob
{
    use AwaitResources;

    /**
     * @throws \Exception
     */
    public function handle()
    {
        if (empty($this->task->data['availability_zone_id'])) {
            $message = 'Unable to deploy management router, no availability_zone_id specified.';
            $this->error($message);
            $this->fail(new \Exception($message));
            return;
        }

        $vpc = $this->task->resource;
        $availabilityZoneId = $this->task->data['availability_zone_id'];

        if (empty($this->task->data['management_router_id'])) {
            $managementRouterCheck = $vpc->routers()->where(function ($query) use ($availabilityZoneId) {
                $query->where('is_management', '=', true);
                $query->where('availability_zone_id', '=', $availabilityZoneId);
            });

            if ($managementRouterCheck->count() > 0) {
                $this->info('A management router was detected, skipping.', [
                    'vpc_id' => $vpc->id,
                    'availability_zone_id' => $availabilityZoneId
                ]);
                $this->task->updateData('management_router_id', $managementRouterCheck->first()->id);
                return;
            }

            $managementRouter = app()->make(Router::class);
            $managementRouter->vpc_id = $vpc->id;
            $managementRouter->name = 'Management Router for ' . $availabilityZoneId . ' - ' . $vpc->id;
            $managementRouter->availability_zone_id = $availabilityZoneId;
            $managementRouter->is_management = true;
            $managementRouter->syncSave();

            // Store the management router id, so we can backoff everything else
            $this->task->updateData('management_router_id', $managementRouter->id);

            $this->info('Creating management router for VPC ' . $vpc->id . ' in availability zone ' . $availabilityZoneId, [
                'management_router_id' => $managementRouter->id,
            ]);
        }

        if ($this->task->data['management_router_id']) {
            $this->awaitSyncableResources([
                $this->task->data['management_router_id']
            ]);
        }
    }
}
