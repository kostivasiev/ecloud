<?php
namespace App\Jobs\VpnSession;

use App\Jobs\Job;
use App\Models\V2\VpnSession;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

class DeletePreSharedKey extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    public function __construct(VpnSession $vpnSession)
    {
        $this->model = $vpnSession;
    }

    public function handle()
    {
        $this->model->credentials()->delete();
    }
}
