<?php

namespace Tests\Solutions;

use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

use App\Models\V1\Solution;

class PostTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testCreateTag()
    {
        $testKey = 'foo';
        $testValue = 'bar';

        factory(Solution::class, 1)->create([
            'ucs_reseller_id' => 123,
        ]);

        $this->missingFromDatabase('metadata', [
            'metadata_resource' => 'ucs_reseller',
            'metadata_resource_id' => 123,
            'metadata_key' => $testKey,
            'metadata_value' => $testValue,
        ]);


        $this->json('POST', '/v1/solutions/123/tags', [
            'key' => $testKey,
            'value' => $testValue,
        ], [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.write',
        ]);

        $this->assertResponseStatus(201) && $this->seeInDatabase('metadata', [
            'metadata_resource' => 'ucs_reseller',
            'metadata_resource_id' => 123,
            'metadata_key' => $testKey,
            'metadata_value' => $testValue,
        ]);
    }
}
