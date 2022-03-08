<?php

namespace Tests\V1;

use App\Models\V1\Solution;

class GetDrsRuleTest extends TestCase
{
    /**
     * Test for valid collection
     * @return void
     */
    public function testExceptionWhenKingpinServiceFails()
    {
        Solution::factory()->create([
            'ucs_reseller_id' => 123,
        ]);

        $this->get('/v1/solutions/123/constraints', [
            'X-consumer-custom-id' => '0-1',
            'X-consumer-groups' => env('APP_NAME') . '.read',
        ])->assertStatus(503);
    }

    /**
     * Test for valid item
     * @return void
     */
    public function testInvalidWhenIsAdminAndSolutionNotFound()
    {
        $this->get('/v1/solutions/123/constraints', [
            'X-consumer-custom-id' => '0-1',
            'X-consumer-groups' => env('APP_NAME') . '.read',
        ])->assertStatus(404);
    }

    /**
     * Test forbidden when not admin
     * @return void
     */
    public function testUnauthorizedWhenNotAdmin()
    {
        Solution::factory()->create([
            'ucs_reseller_id' => 123,
        ]);

        $this->get('/v1/solutions/123/constraints', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => env('APP_NAME') . '.read',
        ])->assertStatus(401);
    }

}
