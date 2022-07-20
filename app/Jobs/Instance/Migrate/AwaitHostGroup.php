<?php

namespace App\Jobs\Instance\Migrate;

use App\Jobs\TaskJob;

class AwaitHostGroup extends TaskJob
{
    public int $tries = 60;

    public int $backoff = 5;

    public function handle()
    {
        if (empty($this->task->data['host_group_id'])) {
            $this->info('Waiting for host group allocation');
            $this->release($this->backoff);
            return;
        }

        $this->info('Host group ' . $this->task->data['host_group_id'] . ' was allocated');
    }
}
