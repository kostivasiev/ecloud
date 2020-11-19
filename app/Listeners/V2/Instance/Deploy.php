<?php

namespace App\Listeners\V2\Instance;

use App\Events\V2\Instance\Deploy as DeployEvent;
use App\Events\V2\Instance\Deploy\Data as DeployEventData;
use App\Jobs\Instance\Deploy\ActivateWindows;
use App\Jobs\Instance\Deploy\AssignFloatingIp;
use App\Jobs\Instance\Deploy\ConfigureNics;
use App\Jobs\Instance\Deploy\ConfigureWinRm;
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
use Illuminate\Support\Facades\Log;

class Deploy implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @param DeployEvent $event
     * @return void
     * @throws Exception
     */
    public function handle(DeployEvent $event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);

        /** @var DeployEventData $data */
        $data = $event->data;

        // TODO :- post MVP, replace this in the jobs so we just pass in the "$event->data"
        $data = (array)$data;

        // Create the chained jobs for deployment
        dispatch((new \App\Jobs\Instance\Deploy\Deploy($event->task, $data))->chain([
            new ConfigureNics($event->task, $data),
            new AssignFloatingIp($event->task, $data),
            new UpdateNetworkAdapter($event->task, $data),
            new OsCustomisation($event->task, $data),
            new PowerOn($event->task, $data),
            new WaitOsCustomisation($event->task, $data),
            new PrepareOsUsers($event->task, $data),
            new PrepareOsDisk($event->task, $data),
            new ConfigureWinRm($event->task, $data),
            new ActivateWindows($event->task, $data),
            new RunApplianceBootstrap($event->task, $data),
            new RunBootstrapScript($event->task, $data),
        ]));

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}
