<?php

namespace Tests\V2\AffinityRuleMembers;

use App\Models\V2\AffinityRule;
use App\Models\V2\AffinityRuleMember;
use Tests\TestCase;

class DeleteTest extends TestCase
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

        $this->affinityRuleMember = AffinityRuleMember::factory([
            'rule_id' => $this->affinityRule->id,
            'instance_id' => $this->instanceModel()->id,
        ])->create();
    }

    public function testDeleteResourceAsUser()
    {
        $this->asUser()
            ->delete(sprintf(static::RESOURCE_URI, $this->affinityRule->id, $this->affinityRuleMember->id))
            ->assertStatus(204);

        $this->assertDatabaseMissing(
            'affinity_rule_members',
            [
                'id' => $this->affinityRuleMember->id,
                'deleted_at' => null,
            ],
            'ecloud'
        );
    }

    public function testDeleteResourceFails()
    {
        $this->asUser()
            ->delete(sprintf(static::RESOURCE_URI, $this->affinityRule->id, '9999'))
            ->assertStatus(404);
    }
}
