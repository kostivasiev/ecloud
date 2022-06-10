<?php

namespace Tests\V2\AffinityRuleMembers;

use App\Events\V2\Task\Created;
use App\Models\V2\AffinityRule;
use App\Models\V2\AffinityRuleMember;
use App\Models\V2\Instance;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
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

        $secondInstance = Instance::withoutEvents(function () {
            return Instance::factory()->create([
                'id' => 'i-test-2',
                'vpc_id' => $this->vpc()->id,
                'name' => 'Test Instance ' . uniqid(),
                'image_id' => $this->image()->id,
                'vcpu_cores' => 1,
                'ram_capacity' => 1024,
                'availability_zone_id' => $this->availabilityZone()->id,
                'deploy_data' => [
                    'network_id' => $this->network()->id,
                    'volume_capacity' => 20,
                    'volume_iops' => 300,
                    'requires_floating_ip' => false,
                ]
            ]);
        });

        $data = [
            'affinity_rule_id' => $this->affinityRule->id,
            'instance_id' => $secondInstance->id,
        ];

        Event::fake([Created::class]);

        $this->asUser()
            ->post(sprintf(static::RESOURCE_URI, $this->affinityRule->id, ''), $data)
            ->assertStatus(202);
    }
}
