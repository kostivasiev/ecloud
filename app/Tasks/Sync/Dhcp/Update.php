<?php

namespace App\Tasks\Sync\Dhcp;

use App\Jobs\Nsx\Dhcp\Create;
use App\Tasks\Task;

class Update extends Task
{
    public function jobs()
    {
        return [
            Create::class
        ];
    }
}
