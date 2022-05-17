<?php

namespace App\Jobs\FloatingIpResource;

use App\Jobs\TaskJob;
use App\Models\V2\Nat;
use App\Models\V2\Natable;
use App\Traits\V2\TaskJobs\AwaitResources;

class CreateDestinationNat extends TaskJob
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

        if (!$floatingIp->destinationNat()->exists()) {
            $this->info('Creating DNAT for floating IP ' . $floatingIp->id);
        }

        $this->createResource(
            Nat::class, [
            'action' => Nat::ACTION_DNAT,
            'sequence' => config('defaults.floating-ip.nat.sequence'),
            'destination_id' => $floatingIp->id,
            'translated_id' => $resource->id
        ],
            function ($nat) use ($floatingIp, $resource) {
                $nat->destination()->associate($floatingIp);
                $nat->translated()->associate($resource);
            });
    }
}
