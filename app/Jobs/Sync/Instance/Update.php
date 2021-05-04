<?php

namespace App\Jobs\Sync\Instance;

use App\Jobs\Instance\ComputeUpdate;
use App\Jobs\Instance\Deploy\ActivateWindows;
use App\Jobs\Instance\Deploy\AssignFloatingIp;
use App\Jobs\Instance\Deploy\AttachOsDisk;
use App\Jobs\Instance\Deploy\AwaitNicSync;
use App\Jobs\Instance\Deploy\CheckNetworkAvailable;
use App\Jobs\Instance\Deploy\ConfigureNics;
use App\Jobs\Instance\Deploy\ConfigureWinRm;
use App\Jobs\Instance\Deploy\Deploy;
use App\Jobs\Instance\Deploy\DeployCompleted;
use App\Jobs\Instance\Deploy\ExpandOsDisk;
use App\Jobs\Instance\Deploy\OsCustomisation;
use App\Jobs\Instance\Deploy\PrepareOsDisk;
use App\Jobs\Instance\Deploy\PrepareOsUsers;
use App\Jobs\Instance\Deploy\RunApplianceBootstrap;
use App\Jobs\Instance\Deploy\RunBootstrapScript;
use App\Jobs\Instance\Deploy\UpdateNetworkAdapter;
use App\Jobs\Instance\Deploy\WaitOsCustomisation;
use App\Jobs\Instance\PowerOn;
use App\Jobs\Job;
use App\Models\V2\Sync;
use App\Traits\V2\JobModel;
use App\Traits\V2\SyncableBatch;

class Update extends Job
{
    use SyncableBatch, JobModel;

    private $sync;

    public function __construct(Sync $sync)
    {
        $this->sync = $sync;
    }

    public function handle()
    {
        if (!$this->sync->resource->deployed) {
            $this->updateSyncBatch([
                [
                    new CheckNetworkAvailable($this->sync->resource),
                    new Deploy($this->sync->resource),
                    new PrepareOsDisk($this->sync->resource),
                    new AttachOsDisk($this->sync->resource),
                    new ConfigureNics($this->sync->resource),
                    new AwaitNicSync($this->sync->resource),
                    new AssignFloatingIp($this->sync->resource),
                    new UpdateNetworkAdapter($this->sync->resource),
                    new OsCustomisation($this->sync->resource),
                    new PowerOn($this->sync->resource),
                    new WaitOsCustomisation($this->sync->resource),
                    new PrepareOsUsers($this->sync->resource),
                    new ExpandOsDisk($this->sync->resource),
                    new ConfigureWinRm($this->sync->resource),
                    new ActivateWindows($this->sync->resource),
                    new RunApplianceBootstrap($this->sync->resource),
                    new RunBootstrapScript($this->sync->resource),
                    new DeployCompleted($this->sync->resource),
                ],
            ])->dispatch();
        } else {
            $this->updateSyncBatch([
                [
                    new ComputeUpdate($this->sync->resource),
                ]
            ])->dispatch();
        }
    }
}
