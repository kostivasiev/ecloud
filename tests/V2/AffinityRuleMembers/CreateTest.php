<?php

namespace Tests\V2\AffinityRuleMembers;

use App\Events\V2\Task\Created;
use App\Models\V2\AffinityRule;
use App\Models\V2\AffinityRuleMember;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateTest extends TestCase
{
    public const RESOURCE_URI = '/v2/affinity-rules/%s/members/%s';
    public AffinityRule $affinityRule;
    public AffinityRuleMember $affinityRuleMember;

    public function setUp(): void
    {
        parent::setUp();
        $this->affinityRule = AffinityRule::factory([
            'availability_zone_id' => $this->availabilityZone()->id,
            'vpc_id' => $this->vpc()->id,
            'type' => 'anti-affinity',
        ])->create();
    }

    public function testCreateAffinityRuleMember()
    {
        $data = [
            'affinity_rule_id' => $this->affinityRule->id,
            'instance_id' => $this->instanceModel()->id,
        ];

        Event::fake([Created::class]);

        $this->asUser()
            ->post(sprintf(static::RESOURCE_URI, $this->affinityRule->id, ''), $data)
            ->assertStatus(202);

        $this->assertDatabaseHas(
            'affinity_rule_members',
            $data,
            'ecloud'
        );
    }

    public function testCreateAffinityRuleInFailedState()
    {
        $affinityRuleMember = AffinityRuleMember::factory()
            ->for($this->affinityRule)
            ->create([
                'instance_id' => $this->instanceModel()->id,
            ]);

        Model::withoutEvents(function () use ($affinityRuleMember) {
            $task = new Task([
                'id' => 'ar-task-1',
                'completed' => false,
                'name' => Sync::TASK_NAME_UPDATE,
                'failure_reason' => 'Task has failed',
            ]);
            $task->resource()->associate($affinityRuleMember);
            $task->save();
        });

        $data = [
            'affinity_rule_id' => $this->affinityRule->id,
            'instance_id' => $this->instanceModel()->id,
        ];

        Event::fake([Created::class]);

        $this->asUser()
            ->post(sprintf(static::RESOURCE_URI, $this->affinityRule->id, ''), $data)
            ->assertStatus(202);
    }
}
