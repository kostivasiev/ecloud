<?php

namespace App\Console\Commands\Instance;

use App\Console\Commands\Command;
use App\Models\V2\Instance;
use App\Models\V2\ResourceTier;

class SetHostGroupToStandard extends Command
{
    protected $signature = 'instance:set-hostgroup {--T|test-run}';
    protected $description = 'Sets unassigned instances to standard hostgroup';

    public function handle()
    {
        Instance::where(function ($query) {
            $query->whereNull('host_group_id');
            $query->orWhere('host_group_id', '=', '');
        })->each(function (Instance $instance) {
            $resourceTier = ResourceTier::find($instance->availabilityZone->resource_tier_id);
            $hostGroup = $resourceTier->getDefaultHostGroup();
            $this->info('Assigning hostgroup ' . $hostGroup->id . ' to ' . $instance->id);
            if (!$this->option('test-run')) {
                $instance->hostGroup()->associate($hostGroup);
                $instance->save();
            }
        });
    }
}
