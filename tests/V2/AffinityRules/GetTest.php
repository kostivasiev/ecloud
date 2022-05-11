<?php

namespace Tests\V2\AffinityRules;

use App\Models\V2\AffinityRule;
use Tests\TestCase;

class GetTest extends TestCase
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

    public function testGetCollectionAsUser()
    {
        $this->asUser()
            ->get(sprintf(static::RESOURCE_URI, ''))
            ->assertJsonFragment([
                'id' => $this->affinityRule->id,
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
            ])->assertStatus(200);
    }

    public function testGetResourceAsUser()
    {
        $this->asUser()
            ->get(sprintf(static::RESOURCE_URI, $this->affinityRule->id))
            ->assertJsonFragment([
                'id' => $this->affinityRule->id,
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
            ])->assertStatus(200);
    }
}