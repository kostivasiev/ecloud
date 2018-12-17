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

//    todo uncomment statusCode check when resource package is updated
//    see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/packages/php/resources/issues/65
//        $this->assertResponseStatus(201) && $this->seeInDatabase('metadata', [
        $this->seeInDatabase('metadata', [
            'metadata_resource' => 'ucs_reseller',
            'metadata_resource_id' => 123,
            'metadata_key' => $testKey,
            'metadata_value' => $testValue,
        ]);
    }
}
