<?php

namespace Tests\V2\AffinityRuleMembers;

use App\Models\V2\AffinityRule;
use App\Models\V2\AffinityRuleMember;
use Tests\TestCase;

class GetTest extends TestCase
{
    public const RULE_RESOURCE_URI = '/v2/affinity-rules/%s/members';
    public const MEMBER_RESOURCE_URI = '/v2/affinity-rule-members/%s';

    private AffinityRule $affinityRule;
    private AffinityRuleMember $affinityRuleMember;

    public function setUp(): void
    {
        parent::setUp();
        $this->affinityRule = AffinityRule::factory([
            'availability_zone_id' => $this->availabilityZone()->id,
            'vpc_id' => $this->vpc()->id,
            'type' => 'affinity',
        ])->create();

        $this->affinityRuleMember = AffinityRuleMember::factory()
            ->create([
                'affinity_rule_id' => $this->affinityRule->id,
                'instance_id' => $this->instanceModel()->id,
            ]);
    }

    public function testGetAffinityRuleMembers()
    {
        $this->asUser()
            ->get(sprintf($this::RULE_RESOURCE_URI, $this->affinityRule->id))
            ->assertJsonFragment([
                'affinity_rule_id' => $this->affinityRule->id,
                'instance_id' => $this->instanceModel()->id,
                'total_pages' => 1
            ])->assertStatus(200);
    }

    public function testGetAffinityRuleMember()
    {
        $this->asUser()
            ->get(sprintf($this::MEMBER_RESOURCE_URI, $this->affinityRuleMember->id))
            ->assertJsonFragment([
                'affinity_rule_id' => $this->affinityRule->id,
                'instance_id' => $this->instanceModel()->id,
            ])
            ->assertJsonMissing([
                'total_pages' => 1
            ])
            ->assertStatus(200);
    }
}
