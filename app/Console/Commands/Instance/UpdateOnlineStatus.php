<?php
namespace App\Console\Commands\Instance;

use App\Models\V2\Instance;
use App\Services\V2\KingpinService;
use Illuminate\Console\Command;

class UpdateOnlineStatus extends Command
{
    protected $signature = 'instance:update-status {--T|test-run}';
    protected $description = 'Updates the online status of all instances';

    public function handle()
    {
        Instance::whereNull('is_online')
            ->each(function ($instance) {
                $this->info('Processing instance: ' . $instance->id);
                $kingpinData = null;
                try {
                    $kingpinResponse = $instance->availabilityZone
                        ->kingpinService()
                        ->get('/api/v2/vpc/' . $instance->vpc_id . '/instance/' . $instance->id);
                    $kingpinData = json_decode($kingpinResponse->getBody()->getContents());
                } catch (\Exception $exception) {
                    $this->info('Failed to retrieve instance ' . $instance->id . ' from Kingpin, skipping');
                    $instance->setAttribute('is_online', false)->saveQuietly();
                    return;
                }
                $state = isset($kingpinData->powerState) ? $kingpinData->powerState == KingpinService::INSTANCE_POWERSTATE_POWEREDON : null;
                if ($state !== $instance->is_online) {
                    $stateString = ($state) ? 'true': 'false';
                    $this->info('Setting online state of ' . $instance->id . ' to ' . $stateString);
                    if (!$this->option('test-run')) {
                        $instance->setAttribute('is_online', $state)->saveQuietly();
                    }
                }
            });
    }
}
