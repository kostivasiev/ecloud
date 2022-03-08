<?php

namespace Tests\V2\DiscountPlan;

use App\Models\V2\DiscountPlan;
use Faker\Factory as Faker;
use Tests\TestCase;

class AcceptRejectTest extends TestCase
{

    protected \Faker\Generator $faker;
    protected $discountPlan;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->discountPlan = DiscountPlan::factory(2)->create([
            'contact_id' => 1,
        ]);
    }

    public function testApproveDiscountPlan()
    {
        $discountPlan = $this->discountPlan->first();
        $this->post(
            '/v2/discount-plans/' . $discountPlan->first()->id . '/approve',
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read, ecloud.write',
                'X-Reseller-Id' => 1,
            ]
        )->assertStatus(200);

        $discountPlan->refresh();

        $this->assertEquals('approved', $discountPlan->status);
        $this->assertNotNull($discountPlan->response_date);
    }

    public function testRejectDiscountPlan()
    {
        $discountPlan = $this->discountPlan[1];
        $this->post(
            '/v2/discount-plans/' . $discountPlan->id . '/reject',
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read, ecloud.write',
                'X-Reseller-Id' => 1,
            ]
        )->assertStatus(200);

        $discountPlan->refresh();

        $this->assertEquals('rejected', $discountPlan->status);
        $this->assertNotNull($discountPlan->response_date);
    }

}