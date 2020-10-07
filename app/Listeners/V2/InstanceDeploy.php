<?php

namespace App\Listeners\V2;

use App\Events\V2\Data\InstanceDeployEventData;
use App\Events\V2\InstanceDeployEvent;
use App\Jobs\Instance\Deploy\AssignFloatingIp;
use App\Jobs\Instance\Deploy\ConfigureNics;
use App\Jobs\Instance\Deploy\Deploy;
use App\Jobs\Instance\Deploy\OsCustomisation;
use App\Jobs\Instance\Deploy\PrepareOsDisk;
use App\Jobs\Instance\Deploy\PrepareOsUsers;
use App\Jobs\Instance\Deploy\RunApplianceBootstrap;
use App\Jobs\Instance\Deploy\RunBootstrapScript;
use App\Jobs\Instance\Deploy\UpdateNetworkAdapter;
use App\Jobs\Instance\Deploy\WaitOsCustomisation;
use App\Jobs\Instance\PowerOn;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class InstanceDeploy implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @param InstanceDeployEvent $event
     * @return void
     * @throws Exception
     */
    public function handle(InstanceDeployEvent $event)
    {
        /** @var InstanceDeployEventData $instanceDeployEventData */
        $instanceDeployEventData = $event->instanceDeployEventData;

        // TODO :- post MVP, replace this in the jobs so we just pass in the "$event->instanceDeployEventData"
        $data = (array)$instanceDeployEventData;

        // Create the chained jobs for deployment
        dispatch((new Deploy($data))->chain([
            new ConfigureNics($data),
            new AssignFloatingIp($data),
            new UpdateNetworkAdapter($data),
            new OsCustomisation($data),
            new PowerOn($data),
            new WaitOsCustomisation($data),
            new PrepareOsUsers($data),
            new PrepareOsDisk($data),
            new RunApplianceBootstrap($data),
            new RunBootstrapScript($data),
        ]));
    }
}
