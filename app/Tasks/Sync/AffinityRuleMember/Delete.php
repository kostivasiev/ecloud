<?php

namespace App\Tasks\Sync\AffinityRuleMember;

use App\Jobs\AffinityRule\AwaitRuleCreation;
use App\Jobs\AffinityRule\AwaitRuleDeletion;
use App\Jobs\AffinityRule\CheckMemberState;
use App\Jobs\AffinityRule\CreateAffinityRule;
use App\Jobs\AffinityRule\DeleteExistingRule;
use App\Tasks\Task;

class Delete extends Task
{
    public function jobs()
    {
        return [
            CheckMemberState::class,
            DeleteExistingRule::class,
            AwaitRuleDeletion::class,
            CreateAffinityRule::class,
            AwaitRuleCreation::class,
        ];
    }
}
