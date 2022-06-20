<?php

namespace Tests\V2\AffinityRuleMembers;

use App\Events\V2\Task\Created;
use App\Models\V2\AffinityRule;
use App\Models\V2\AffinityRuleMember;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    public const RESOURCE_URI = '/v2/affinity-rule-members/%s';
    public AffinityRule $affinityRule;

    public function setUp(): void
    {
        parent::setUp();
        $this->affinityRule = AffinityRule::factory([
            'availability_zone_id' => $this->availabilityZone()->id,
            'vpc_id' => $this->vpc()->id,
            'type' => 'affinity',
        ])->create();

        $this->affinityRuleMember = AffinityRuleMember::factory([
            'affinity_rule_id' => $this->affinityRule->id,
            'instance_id' => $this->instanceModel()->id,
        ])->create();
    }

    public function testDeleteResourceAsUser()
    {
        Event::fake([Created::class]);

        $this->asUser()
            ->delete(sprintf(static::RESOURCE_URI, $this->affinityRuleMember->id))
            ->assertStatus(202);
    }

    public function testDeleteResourceFails()
    {
        $this->asUser()
            ->delete(sprintf(static::RESOURCE_URI,  '9999'))
            ->assertStatus(404);
    }
}
