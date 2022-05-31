<?php

namespace Tests\V2\AffinityRules;

use App\Models\V2\AffinityRule;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    public AffinityRule $affinityRule;
    public const RESOURCE_URI = '/v2/affinity-rules/%s';

    public function setUp(): void
    {
        parent::setUp();
        $this->affinityRule = AffinityRule::factory()
            ->create([
                'type' => 'anti-affinity',
                'availability_zone_id' => $this->availabilityZone(),
                'vpc_id' => $this->vpc(),
            ]);
    }

    public function testDeleteResourceAsUser()
    {
        $this->asUser()
            ->delete(sprintf(static::RESOURCE_URI, $this->affinityRule->id))
            ->assertStatus(202);

        $this->assertDatabaseMissing(
            'affinity_rules',
            [
                'id' => $this->affinityRule->id,
                'deleted_at' => null,
            ],
            'ecloud'
        );
    }

    public function testDeleteResourceFails()
    {
        $this->asUser()
            ->delete(sprintf(static::RESOURCE_URI, '9999'))
            ->assertStatus(404);
    }
}