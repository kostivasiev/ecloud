<?php

namespace App\Jobs\Instance\Deploy;

/** @var DeployEventData $data */

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
use App\Jobs\TaskJob;
use App\Models\V2\Instance;


class TestTaskJob extends TaskJob
{
    private $data;

    public function __construct(Instance $instance, array $data)
    {
        parent::__construct($instance);

        $this->data = $data;
    }

    public function handle()
    {
        // Create the chained jobs for deployment
        dispatch((new \App\Jobs\Instance\Deploy\Deploy($this->data))->chain([
            new ConfigureNics($this->data),
            new AssignFloatingIp($this->data),
            new UpdateNetworkAdapter($this->data),
            new OsCustomisation($this->data),
            new PowerOn($this->data),
            new WaitOsCustomisation($this->data),
            new PrepareOsUsers($this->data),
            new PrepareOsDisk($this->data),
            new ConfigureWinRm($this->data),
            new ActivateWindows($this->data),
            new RunApplianceBootstrap($this->data),
            new RunBootstrapScript($this->data),
        ]));
    }
}