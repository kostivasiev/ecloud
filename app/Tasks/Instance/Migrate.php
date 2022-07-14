<?php

namespace App\Tasks\Instance;

use App\Jobs\Instance\MigrateToHostGroup;
use App\Tasks\Task;

class Migrate extends Task
{
    public static string $name = 'instance_migrate';

    public function jobs()
    {
        return [
            MigrateToHostGroup::class,
        ];
    }
}
