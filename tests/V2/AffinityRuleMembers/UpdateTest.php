<?php

namespace Tests\V2\AffinityRuleMembers;

use App\Models\V2\AffinityRule;
use App\Models\V2\AffinityRuleMember;
use App\Models\V2\Vpc;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    public const RESOURCE_URI = '/v2/affinity-rules/%s/members/%s';

    private AffinityRule $affinityRule;
    private AffinityRule $anotherAffinityRule;
    private AffinityRuleMember $affinityRuleMember;

    public function setUp(): void
    {
        parent::setUp();
        $this->affinityRule = AffinityRule::factory([
            'availability_zone_id' => $this->availabilityZone()->id,
            'vpc_id' => $this->vpc()->id,
            'type' => 'affinity',
        ])->create();

        $this->anotherAffinityRule = AffinityRule::factory([
            'availability_zone_id' => $this->availabilityZone()->id,
            'vpc_id' => Vpc::factory()->create(),
            'type' => 'affinity',
        ])->create();

        $this->affinityRuleMember = AffinityRuleMember::factory()
            ->create([
                'rule_id' => $this->affinityRule->id,
                'instance_id' => $this->instanceModel()->id,
            ]);
    }

    public function testUpdateResource()
    {
        $this->markTestSkipped();

        $data = [
            'rule_id' => $this->anotherAffinityRule->id,
        ];
        $this->asUser()
            ->patch(
                sprintf(static::RESOURCE_URI, $this->affinityRule->id, $this->affinityRuleMember->id),
                $data
            )->assertStatus(202);
        $this->assertDatabaseHas(
            'affinity_rule_members',
            $data + ['id' => $this->affinityRuleMember->id],
            'ecloud'
        );
    }
}
