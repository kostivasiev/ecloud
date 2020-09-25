<?php

namespace App\Console\Commands\Kingpin\Instance;

use App\Models\V2\Instance;
use App\Services\V2\KingpinService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Class DeleteInstance
 * Delete an instance from vmware
 * @package App\Console\Commands\Kingpin\Instance
 * @param string instanceId
 */
class Delete extends Command
{
    protected $signature = 'kingpin:instance:delete {instanceId}';
    protected $description = 'Delete an instance';

    public function handle()
    {
        /** @var Instance $instance */
        $instance = Instance::find($this->argument('instanceId'));
        if (!$instance) {
            $this->alert('Failed to find instance');
            exit;
        }

        try {
            $kingpinService = app()->make(KingpinService::class, [$instance->availabilityZone]);
            $kingpinService->delete('/api/v2/vpc/' . $instance->vpc_id . '/instance/' . $instance->getKey());
        } catch (\Exception $e) {
            $errorMessage = 'Failed to delete instance' . $e->getMessage();
            $this->output->writeln($errorMessage);
            Log::error($errorMessage);
        }
    }
}
