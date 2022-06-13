<?php

namespace App\Tasks\Sync\AffinityRuleMember;

use App\Jobs\AffinityRuleMember\AwaitRuleCreation;
use App\Jobs\AffinityRuleMember\AwaitRuleDeletion;
use App\Jobs\AffinityRuleMember\CreateAffinityRule;
use App\Jobs\AffinityRuleMember\DeleteExistingRule;
use App\Tasks\Task;

class Update extends Task
{
    public function jobs()
    {
        return [
            DeleteExistingRule::class,
            AwaitRuleDeletion::class,
            CreateAffinityRule::class,
            AwaitRuleCreation::class,
        ];
    }
}
