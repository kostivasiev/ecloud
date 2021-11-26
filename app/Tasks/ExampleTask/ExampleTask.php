<?php

namespace App\Tasks\ExampleTask;

use App\Jobs\Instance\PowerOn;
use App\Jobs\Volume\AssignPort;
use App\Tasks\Task;

class ExampleTask extends Task
{
    public function jobs()
    {
        return [
            AssignPort::class,
            PowerOn::class,
        ];
    }
}