<?php

namespace Tests\V2\DiscountPlan;

use App\Models\V2\DiscountPlan;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class AcceptRejectTest extends TestCase
{

    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected DiscountPlan $discountPlan;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->discountPlan = factory(DiscountPlan::class)->create([
            'contact_id' => 1,
        ]);
    }

    public function testApproveDiscountPlan()
    {
        $this->post(
            '/v2/discount-plans/'.$this->discountPlan->getKey().'/approve',
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read, ecloud.write',
                'X-Reseller-Id' => 1,
            ]
        )->assertResponseStatus(200);

        $this->discountPlan->refresh();

        $this->assertEquals('approved', $this->discountPlan->status);
        $this->assertNotNull($this->discountPlan->response_date);
    }
}