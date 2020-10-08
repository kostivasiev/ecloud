<?php

namespace App\Listeners\V2\Instance;

use App\Events\V2\Instance\Deploy\Data as DeployEventData;
use App\Events\V2\Instance\Deploy as DeployEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class Deploy implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @param DeployEvent $event
     * @return void
     * @throws \Exception
     */
    public function handle(DeployEvent $event)
    {
        /** @var DeployEventData $data */
        $data = $event->data;

        // TODO :- post MVP, replace this in the jobs so we just pass in the "$event->data"
        $data = (array)$data;

        // Create the chained jobs for deployment
        dispatch((new \App\Jobs\Instance\Deploy\Deploy($data))->chain([
//            new \App\Jobs\Instance\Deploy\ConfigureNics($data),
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
