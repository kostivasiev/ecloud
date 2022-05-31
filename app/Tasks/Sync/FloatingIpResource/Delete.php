<?php

namespace App\Tasks\Sync\FloatingIpResource;

use App\Jobs\FloatingIpResource\DeleteDestinationNat;
use App\Jobs\FloatingIpResource\DeleteSourceNat;
use App\Tasks\Task;

class Delete extends Task
{
    public function jobs()
    {
        return [
            DeleteSourceNat::class,
            DeleteDestinationNat::class,
        ];
    }
}
