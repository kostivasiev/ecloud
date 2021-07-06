<?php

namespace App\Jobs\OrchestratorBuild;

use App\Jobs\Job;
use App\Models\V2\Image;
use App\Models\V2\Instance;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\Vpc;
use App\Support\Sync;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateInstances extends Job
{
    use Batchable, LoggableModelJob;

    private OrchestratorBuild $model;

    public function __construct(OrchestratorBuild $orchestratorBuild)
    {
        $this->model = $orchestratorBuild;
    }

    public function handle()
    {
        $orchestratorBuild = $this->model;

        $data = collect(json_decode($orchestratorBuild->orchestratorConfig->data));

        if (!$data->has('instances')) {
            Log::info(get_class($this) . ' : OrchestratorBuild does not contain any instances, skipping', ['id' => $this->model->id]);
            return;
        }

        collect($data->get('instances'))->each(function ($definition, $index) use ($orchestratorBuild) {
            // Check if a resource has already been created
            if (isset($orchestratorBuild->state['instance']) && isset($orchestratorBuild->state['instance'][$index])) {
                Log::info(get_class($this) . ' : OrchestratorBuild instance. ' . $index . ' has already been initiated, skipping', ['id' => $this->model->id]);
                return;
            }

            $definition = $orchestratorBuild->render($definition);

            $instance = app()->make(Instance::class);
            $instance->fill(
                $definition->only([
                    'name',
                    'vpc_id',
                    'image_id',
                    'vcpu_cores',
                    'ram_capacity',
                    'locked',
                    'backup_enabled',
                    'host_group_id'
                ])->toArray()
            );

            $instance->locked = $definition->has('locked') && $definition->get('locked') === true;

            $availabilityZoneId = $this->getAvailabilityZoneId($definition);
            if (!$availabilityZoneId) {
                $this->fail(new \Exception('Failed to determine availability zone ID for instance ' . $index));
                return;
            }

            $instance->availability_zone_id = $availabilityZoneId;

            $networkId = $this->getNetworkId($definition);
            if (!$networkId) {
                $this->fail(new \Exception('Failed to determine network ID for instance ' . $index));
                return;
            }

            $image = Image::findOrFail($definition->get('image_id'));

            $instance->deploy_data = [
                'volume_capacity' => $definition->get('volume_capacity', config('volume.capacity.' . strtolower($image->platform) . '.min')),
                'volume_iops' => $definition->get('volume_iops', config('volume.iops.default')),
                'network_id' => $definition->get('network_id', $networkId),
                'floating_ip_id' => $definition->get('floating_ip_id'),
                'requires_floating_ip' => $definition->get('requires_floating_ip', false),
                'image_data' => $definition->get('image_data'),
                'user_script' => $definition->get('user_script'),
                'ssh_key_pair_ids' => $definition->get('ssh_key_pair_ids'),
            ];

            $instance->syncSave();

            Log::info(get_class($this) . ' : OrchestratorBuild created instance ' . $instance->id, ['id' => $this->model->id]);

            $orchestratorBuild->updateState('instance', $index, $instance->id);
        });
    }

    private function getNetworkId($definition)
    {
        if ($definition->has('network_id')) {
            return $definition->get('network_id');
        }

        $vpc = Vpc::findOrFail($definition->get('vpc_id'));

        if ($vpc->routers->count() == 1 && $vpc->routers->first()->networks->count() == 1 && $vpc->routers->first()->sync->status !== Sync::STATUS_FAILED) {
            return $vpc->routers->first()->networks->first()->id;
        }

        return false;
    }

    private function getAvailabilityZoneId($definition)
    {
        if ($definition->has('availability_zone_id')) {
            return $definition->get('availability_zone_id');
        }

        $vpc = Vpc::findOrFail($definition->get('vpc_id'));

        return $vpc->region()->first()->availabilityZones()->first()->id;
    }

}
