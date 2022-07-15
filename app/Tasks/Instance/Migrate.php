<?php

namespace App\Tasks\Instance;

use App\Jobs\Instance\Migrate\AwaitHostGroup;
use App\Jobs\Instance\Migrate\MigrateToHostGroup;
use App\Jobs\Instance\Migrate\PowerOff;
use App\Jobs\Instance\Migrate\PowerOn;
use App\Tasks\Task;

class Migrate extends Task
{
    public static string $name = 'instance_migrate';

    public function jobs()
    {
        return [
            AwaitHostGroup::class,
            PowerOff::class,
            MigrateToHostGroup::class,
            PowerOn::class,
        ];
    }
}
