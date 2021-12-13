<?php

namespace App\Jobs\LoadBalancer;

use App\Jobs\Job;
use App\Models\V2\Image;
use App\Models\V2\LoadBalancer;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\OrchestratorConfig;
use App\Models\V2\Task;
use App\Traits\V2\Jobs\AwaitResources;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateInstances extends Job
{
    use Batchable, LoggableModelJob, AwaitResources;
    
    private LoadBalancer $model;

    private Task $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->model = $this->task->resource;
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $loadBalancer = $this->model;

        if (empty($this->task->data['orchestrator_build_id'])) {
            $spec = $this->model->loadBalancerSpec;
            $imageId = $spec->image_id;

            if (!($image = Image::find($imageId))) {
                $this->fail(new \Exception('Failed to load balancer image to create a new load balancer'));
                return;
            }

            $managementRouter = $loadBalancer->availabilityZone->routers()
                ->where('is_management', true)
                ->whereHas('vpc', function ($query) use ($loadBalancer) {
                    $query->where('id', $loadBalancer->vpc->id);
                })->first();

            if (!$managementRouter) {
                Log::error('Failed to load management router', [
                    'vpc_id' => $loadBalancer->vpc->id,
                    'availability_zone_id' => $loadBalancer->availabilityZone->id,
                    'load_balancer_id' => $loadBalancer->id]);

                $this->fail(new \Exception('Failed to load management router'));
                return;
            }

            $managementNetwork = $managementRouter->networks->first();

            if (!$managementNetwork) {
                Log::error('Failed to load management network', [
                    'vpc_id' => $loadBalancer->vpc->id,
                    'availability_zone_id' => $loadBalancer->availabilityZone->id,
                    'load_balancer_id' => $loadBalancer->id]);

                $this->fail(new \Exception('Failed to load management network'));
                return;
            }

            for ($i = 0; $i < $loadBalancer->loadBalancerSpec->node_count; $i++) {
                $orchestratorData['instances'][] = [
                    'name' => 'Load Balancer ' . $i,
                    'vpc_id' => $loadBalancer->vpc->id,
                    'image_id' => $image->id,
                    'vcpu_cores' => $loadBalancer->loadBalancerSpec->cpu,
                    'ram_capacity' => (1024 * $loadBalancer->loadBalancerSpec->ram),
                    'network_id' => $managementNetwork->id,
                    'volume_capacity' => $loadBalancer->loadBalancerSpec->hdd,
                    'volume_iops' => $loadBalancer->loadBalancerSpec->iops,
                    'image_data' => [
                        //TODO: we will need to expand upon this, see https://gitlab.devops.ukfast.co.uk/ukfast/infrastructure/appliance-service/-/blob/master/ubuntu2004-lbv2/README.md
                        "node_id" => ($i+1),
                    ],
                    'load_balancer_id' => $loadBalancer->id,
                    'is_hidden' => true
                ];
            }

            // Create an orchestrator config
            $orchestratorConfig = app()->make(OrchestratorConfig::class, [
                'reseller_id' => $loadBalancer->vpc->reseller_id
            ]);
            $orchestratorConfig->data = json_encode($orchestratorData);
            $orchestratorConfig->save();

            // Trigger the build
            $orchestratorBuild = app()->make(OrchestratorBuild::class);
            $orchestratorBuild->orchestratorConfig()->associate($orchestratorConfig);
            $orchestratorBuild->syncSave();

            // Store the management router id, so we can backoff everything else
            $this->task->data = [
                'orchestrator_build_id' => $orchestratorBuild->id
            ];
            $this->task->saveQuietly();
        } else {
            $orchestratorBuild = OrchestratorBuild::findOrFail($this->task->data['orchestrator_build_id']);
        }

        if (!empty($orchestratorBuild)) {
            $this->awaitSyncableResources([
                $orchestratorBuild->id,
            ]);
        }
    }
}
