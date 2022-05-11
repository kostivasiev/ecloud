<?php

namespace Tests\V1\Solutions;

use App\Models\V1\Solution;
use Tests\V1\TestCase;

class PostTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testCreateTag()
    {
        $testKey = 'foo';
        $testValue = 'bar';

        Solution::factory()->create([
            'ucs_reseller_id' => 123,
        ]);

        $this->assertDatabaseMissing('metadata', [
            'metadata_resource' => 'ucs_reseller',
            'metadata_resource_id' => 123,
            'metadata_key' => $testKey,
            'metadata_value' => $testValue,
        ]);


        $this->post('/v1/solutions/123/tags', [
            'key' => $testKey,
            'value' => $testValue,
        ], [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertStatus(201);
        $this->assertDatabaseHas('metadata', [
            'metadata_resource' => 'ucs_reseller',
            'metadata_resource_id' => 123,
            'metadata_key' => $testKey,
            'metadata_value' => $testValue,
        ]);
    }
}
