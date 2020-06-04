<?php

namespace Tests\V1\Solutions;

use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

use App\Models\V1\Solution;
use App\Models\V1\Tag;

class DeleteTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testDeleteTag()
    {
        factory(Solution::class, 1)->create([
            'ucs_reseller_id' => 123,
        ]);

        factory(Tag::class, 1)->create([
            'metadata_resource' => 'ucs_reseller',
            'metadata_resource_id' => 123,
            'metadata_key' => 'test',
        ]);

        $this->json('DELETE', '/v1/solutions/123/tags/test', [

        ], [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.write',
        ]);

        $this->assertResponseStatus(204) && $this->missingFromDatabase('metadata', [
            'metadata_resource' => 'ucs_reseller',
            'metadata_resource_id' => 123,
            'metadata_key' => 'test',
        ]);
    }
}
