<?php

namespace Tests\V2\AffinityRules;

use App\Models\V2\AffinityRule;
use Tests\TestCase;

class UpdateTest extends TestCase
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

    public function testUpdateResource()
    {
        $data = [
            'name' => 'UPDATED',
            'type' => 'affinity',
        ];
        $this->asUser()
            ->patch(
                sprintf(static::RESOURCE_URI, $this->affinityRule->id),
                $data
            )->assertStatus(202);
        $this->assertDatabaseHas(
            'affinity_rules',
            $data + ['id' => $this->affinityRule->id],
            'ecloud'
        );
    }

    public function testUpdateResourceBadTypeFails()
    {
        $this->asUser()
            ->patch(
                sprintf(static::RESOURCE_URI, $this->affinityRule->id),
                [
                    'type' => 'INVALID',
                ]
            )->assertJsonFragment([
                'title' => 'Validation Error',
                'detail' => 'The selected type is invalid',
            ])->assertStatus(422);
    }
}
