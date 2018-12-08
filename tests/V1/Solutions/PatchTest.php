<?php

namespace Tests\Solutions;

use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

use App\Models\V1\Solution;

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
        $testString = 'Super Simulated Solution';

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


        $this->assertResponseStatus(200) && $this->seeInDatabase('ucs_reseller', [
            'ucs_reseller_id' => 123,
            'ucs_reseller_solution_name' => $testString,
        ]);
    }
}
