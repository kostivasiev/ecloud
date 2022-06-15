<?php

namespace App\Console\Commands\FloatingIp;

use App\Console\Commands\Command;
use App\Models\V2\FloatingIp;
use App\Models\V2\FloatingIpResource;

class MigratePolymorphicRelationshipToPivot extends Command
{
    protected $signature = 'floating-ip:migrate-polymorphic-relationship {-T|--test-run}';

    protected $description = 'Migrates the polymorphic relationship from existing fips to the pivot table floating_ip_resource';

    public function handle()
    {
        $updated = 0;
        $skipped = 0;

        FloatingIp::all()->each(function ($floatingIp) use (&$updated, &$skipped) {
            if (!is_null($floatingIp->getRawOriginal('resource_id'))) {
                $floatingIpResource = FloatingIpResource::firstOrNew([
                    'floating_ip_id' => $floatingIp->id,
                    'resource_id' => $floatingIp->getRawOriginal('resource_id'),
                    'resource_type' => $floatingIp->resource_type
                ]);

                $this->info('Creating pivot for ' . $floatingIp->id . ' to resource ' . $floatingIp->resource_id);

                if (!$this->option('test-run')) {
                    $floatingIpResource->save();

                    if ($floatingIpResource->wasRecentlyCreated) {
                        $updated++;
                    } else {
                        $this->info('pivot already exists, skipping');
                        $skipped++;
                    }
                } else {
                    $updated++;
                }
            }
        });

        $this->info($updated . ' floating IP pivots created');
        $this->info($skipped . ' floating IP\'s skipped');

        return 0;
    }
}