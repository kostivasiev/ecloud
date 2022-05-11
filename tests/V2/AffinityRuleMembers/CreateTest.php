<?php

namespace Tests\V2\AffinityRuleMembers;

use App\Models\V2\AffinityRule;
use Tests\TestCase;

class CreateTest extends TestCase
{
    public const RESOURCE_URI = '/v2/affinity-rules/%s/members/%s';
    public AffinityRule $affinityRule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->affinityRule = AffinityRule::factory([
            'availability_zone_id' => $this->availabilityZone()->id,
            'vpc_id' => $this->vpc()->id,
            'type' => 'affinity',
        ])->create();
    }

    public function testCreateAffinityRuleMember()
    {
        $data = [
            'rule_id' => $this->affinityRule->id,
            'instance_id' => $this->instanceModel()->id,
        ];

        $this->asUser()
            ->post(sprintf(static::RESOURCE_URI, $this->affinityRule->id, ''), $data)
            ->assertStatus(202);

        $this->assertDatabaseHas(
            'affinity_rule_members',
            $data,
            'ecloud'
        );
    }
}
