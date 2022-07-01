<?php

namespace App\Jobs\Instance;

use App\Jobs\TaskJob;

class AssociateHostGroup extends TaskJob
{
    public function handle()
    {
        $instance = $this->task->resource;
        $hostGroupId = $this->task->data['host_group_id'];

        $instance->hostGroup()->associate($hostGroupId);
        $instance->saveQuietly();
    }
}
