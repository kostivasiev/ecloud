<?php

namespace Tests\V2\AffinityRules;

use App\Models\V2\AffinityRule;
use Tests\TestCase;

class CreateTest extends TestCase
{
    public const RESOURCE_URI = '/v2/affinity-rules';

    public function testCreateAffinityRule()
    {
        $data = [
            'availability_zone_id' => $this->availabilityZone()->id,
            'vpc_id' => $this->vpc()->id,
            'type' => 'anti-affinity',
        ];
        $this->asUser()
            ->post(static::RESOURCE_URI, $data)
            ->assertStatus(202);

        $this->assertDatabaseHas(
            'affinity_rules',
            $data,
            'ecloud'
        );
    }

    public function testBadTypeIsFailed()
    {
        $this->asUser()
            ->post(static::RESOURCE_URI, [
                'availability_zone_id' => $this->availabilityZone()->id,
                'vpc_id' => $this->vpc()->id,
                'type' => 'INVALID',
            ])->assertJsonFragment([
                'title' => 'Validation Error',
                'detail' => 'The selected type is invalid',
            ])->assertStatus(422);
    }
}
