<?php

namespace Tests\V1\Solutions;

use App\Models\V1\Solution;
use App\Models\V1\Tag;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\V1\TestCase;

class DeleteTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testDeleteTag()
    {
        Solution::factory()->create([
            'ucs_reseller_id' => 123,
        ]);

        Tag::factory()->create([
            'metadata_resource' => 'ucs_reseller',
            'metadata_resource_id' => 123,
            'metadata_key' => 'test',
        ]);

        $this->deleteJson(
            '/v1/solutions/123/tags/test',
            [],
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertStatus(204);

        $this->assertDatabaseMissing('metadata', [
            'metadata_resource' => 'ucs_reseller',
            'metadata_resource_id' => 123,
            'metadata_key' => 'test',
        ]);
    }
}
