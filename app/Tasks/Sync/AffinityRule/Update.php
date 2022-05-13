<?php

namespace App\Tasks\Sync\AffinityRule;

use App\Jobs\AffinityRule\CheckMemberState;
use App\Jobs\AffinityRule\CreateAffinityRule;
use App\Tasks\Task;

class Update extends Task
{
    public function jobs()
    {
        return [
            CheckMemberState::class,
            CreateAffinityRule::class,
        ];
    }
}
