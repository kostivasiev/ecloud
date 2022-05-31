<?php

namespace App\Tasks\Sync\FloatingIpResource;

use App\Jobs\FloatingIpResource\CreateDestinationNat;
use App\Jobs\FloatingIpResource\CreateSourceNat;
use App\Tasks\Task;

class Update extends Task
{
    public function jobs()
    {
        return [
            CreateSourceNat::class,
            CreateDestinationNat::class,
        ];
    }
}
