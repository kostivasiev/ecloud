<?php

namespace Tests\Solutions;

use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

use App\Models\V1\Solution;
use App\Models\V1\Tag;

class PatchTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test for valid collection
     * @return void
     */
    public function testSetName()
    {
        $testString = 'phpUnit test string';

        factory(Solution::class, 1)->create([
            'ucs_reseller_id' => 123,
        ]);

        
        $this->missingFromDatabase('ucs_reseller', [
            'ucs_reseller_id' => 123,
            'ucs_reseller_solution_name' => $testString,
        ]);


        $this->json('PATCH', '/v1/solutions/123', [
            'name' => $testString,
        ], [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.write',
        ]);


        $this->assertResponseStatus(204) && $this->seeInDatabase('ucs_reseller', [
            'ucs_reseller_id' => 123,
            'ucs_reseller_solution_name' => $testString,
        ]);
    }

    public function testSetTag()
    {
        $testString = 'phpUnit test string';

        factory(Solution::class, 1)->create([
            'ucs_reseller_id' => 123,
        ]);

        factory(Tag::class, 1)->create([
            'metadata_resource' => 'ucs_reseller',
            'metadata_resource_id' => 123,
            'metadata_key' => 'test',
        ]);


        $this->missingFromDatabase('metadata', [
            'metadata_resource' => 'ucs_reseller',
            'metadata_resource_id' => 123,
            'metadata_key' => 'test',
            'metadata_value' => $testString,
        ]);


        $this->json('PATCH', '/v1/solutions/123/tags/test', [
            'value' => $testString,
        ], [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.write',
        ]);


        $this->assertResponseStatus(200) && $this->seeInDatabase('metadata', [
            'metadata_resource' => 'ucs_reseller',
            'metadata_resource_id' => 123,
            'metadata_key' => 'test',
            'metadata_value' => $testString,
        ]);
    }
}
