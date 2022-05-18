<?php

namespace App\Jobs\FloatingIpResource;

use App\Jobs\TaskJob;
use App\Models\V2\Nat;
use App\Models\V2\Natable;
use App\Traits\V2\TaskJobs\AwaitResources;

class CreateSourceNat extends TaskJob
{
    use AwaitResources;

    /**
     * Create NAT's between NATable resources
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function handle()
    {
        $floatingIpResource = $this->task->resource;

        $floatingIp = $floatingIpResource->floatingIp;
        $resource = $floatingIpResource->resource;

        if (!($resource instanceof Natable)) {
            $this->info('Resource is not a Natable resource, skipping');
            return;
        }

        if (!$floatingIp->sourceNat()->exists()) {
            $this->info('Creating SNAT for floating IP ' . $floatingIp->id);
        }

        $this->createSyncableResource(
            Nat::class, [
            'action' => Nat::ACTION_SNAT,
            'sequence' => config('defaults.floating-ip.nat.sequence'),
            'source_id' => $resource->id,
            'translated_id' => $floatingIp->id
        ],
            function ($nat) use ($floatingIp, $resource) {
                $nat->source()->associate($resource);
                $nat->translated()->associate($floatingIp);
            });
    }
}
