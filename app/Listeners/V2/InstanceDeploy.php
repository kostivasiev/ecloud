<?php

namespace App\Listeners\V2;

use App\Events\V2\Data\InstanceDeployEventData;
use App\Events\V2\InstanceDeployEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class InstanceDeploy implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @param InstanceDeployEvent $event
     * @return void
     * @throws \Exception
     */
    public function handle(InstanceDeployEvent $event)
    {
        /** @var InstanceDeployEventData $instanceDeployEventData */
        $instanceDeployEventData = $event->instanceDeployEventData;

        // TODO :- post MVP, replace this in the jobs so we just pass in the "$event->instanceDeployEventData"
        $data = (array)$instanceDeployEventData;

        // Create the chained jobs for deployment
        dispatch((new \App\Jobs\Instance\Deploy\Deploy($data))->chain([
            new \App\Jobs\Instance\Deploy\ConfigureNics($data),
            new \App\Jobs\Instance\Deploy\UpdateNetworkAdapter($data),
            new \App\Jobs\Instance\Deploy\OsCustomisation($data),
            new \App\Jobs\Instance\PowerOn($data),
            new \App\Jobs\Instance\Deploy\WaitOsCustomisation($data),
            new \App\Jobs\Instance\Deploy\PrepareOsUsers($data),
            new \App\Jobs\Instance\Deploy\PrepareOsDisk($data),
            new \App\Jobs\Instance\Deploy\RunApplianceBootstrap($data),
            new \App\Jobs\Instance\Deploy\RunBootstrapScript($data),
        ]));
    }
}
