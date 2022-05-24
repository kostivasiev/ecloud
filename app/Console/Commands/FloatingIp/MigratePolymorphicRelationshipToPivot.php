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
            if ($floatingIp->resource()->exists()) {
                $this->info('Creating pivot for ' . $floatingIp->id . ' to resource' . $floatingIp->resource->id);

                if (!$this->option('test-run')) {
                    $floatingIpResource = FloatingIpResource::firstOrNew([
                        'floating_ip_id' => $floatingIp->id
                    ]);

                    $floatingIpResource->resource()->associate($floatingIp->resource);
                    $floatingIpResource->save();

                    if($floatingIpResource->wasRecentlyCreated) {
                        $updated++;
                    } else {
                        $this->info('pivot already exists, skipping');
                        $skipped++;
                    }
                }
            }
        });

        $this->info($updated . ' floating IP\'s updated');
        $this->info($skipped . ' floating IP\'s skipped');

        return 0;
    }
}
