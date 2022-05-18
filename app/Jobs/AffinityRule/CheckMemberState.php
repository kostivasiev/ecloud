<?php

namespace App\Jobs\AffinityRule;

use App\Jobs\Job;
use App\Models\V2\AffinityRuleMember;
use App\Models\V2\Task;
use App\Support\Sync;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

class CheckMemberState extends Job
{
    use Batchable, LoggableModelJob;

    private $task;
    private $model;

    public $backoff = 5;

    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->model = $this->task->resource;
    }

    public function handle()
    {
        $this->model
            ->affinityRuleMembers()
            ->each(function (AffinityRuleMember $affinityRuleMember) {
                if ($affinityRuleMember->sync->status == Sync::STATUS_FAILED) {
                    $this->fail(
                        new \Exception($affinityRuleMember->id . ' is in a failed state')
                    );
                    return false;
                } else if ($affinityRuleMember->sync->status == Sync::STATUS_COMPLETE) {
                    return true;
                }
                $this->release($this->backoff);
                return false;
            });
    }
}
