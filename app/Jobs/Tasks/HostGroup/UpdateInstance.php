<?php
namespace App\Jobs\Tasks\HostGroup;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

class UpdateInstance extends Job
{
    use Batchable, LoggableModelJob;

    private $model;
    private $hostGroupId;

    public function __construct(Instance $instance, string $hostGroupId)
    {
        $this->model = $instance;
        $this->hostGroupId = $hostGroupId;
    }

    public function handle()
    {
        $this->model->host_group_id = $this->hostGroupId;
        $this->model->saveQuietly();
    }
}
