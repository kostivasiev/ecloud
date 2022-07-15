<?php

namespace App\Jobs\OrchestratorBuild;

use App\Jobs\Job;
use App\Models\V2\Image;
use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\ResourceTier;
use App\Models\V2\Vpc;
use App\Support\Sync;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Auth;
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

            $quantity = $definition->has('quantity') ? $definition->get('quantity') : 1;
            $instanceIds = [];
            for ($i=0; $i < $quantity; $i++) {
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
                        'host_group_id',
                        'load_balancer_id',
                        'is_hidden'
                    ])->toArray()
                );
                if ($quantity > 1) {
                    $instance->setAttribute('name', $definition->get('name') . ' #' . ($i+1));
                }

                if ($definition->has('resource_tier_id') && !$definition->has('host_group_id')) {
                    $resourceTier = ResourceTier::find($definition->get('resource_tier_id'));
                    $instance->hostGroup()->associate($resourceTier->getDefaultHostGroup());
                }

                $instance->locked = $definition->has('locked') && $definition->get('locked') === true;

                $network = Network::findOrFail($definition->get('network_id'));
                $instance->availabilityZone()->associate($network->router->availabilityZone);

                $image = Image::findOrFail($definition->get('image_id'));

                $instance->deploy_data = [
                    'volume_capacity' => $definition->get('volume_capacity', config('volume.capacity.' . strtolower($image->platform) . '.min')),
                    'volume_iops' => $definition->get('volume_iops', config('volume.iops.default')),
                    'network_id' => $definition->get('network_id'),
                    'floating_ip_id' => $definition->get('floating_ip_id'),
                    'requires_floating_ip' => $definition->get('requires_floating_ip', false),
                    'image_data' => $definition->get('image_data'),
                    'user_script' => $definition->get('user_script'),
                    'ssh_key_pair_ids' => $definition->get('ssh_key_pair_ids'),
                    'software_ids' => $definition->get('software_ids'),
                ];

                $instance->syncSave();

                Log::info(get_class($this) . ' : OrchestratorBuild created instance ' . $instance->id, ['id' => $this->model->id]);
                $instanceIds[] = $instance->id;
            }
            $orchestratorBuild->updateState('instance', $index, $instanceIds);
        });
    }
}
