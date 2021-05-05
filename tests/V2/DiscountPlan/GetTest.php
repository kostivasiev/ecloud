<?php

namespace Tests\V2\DiscountPlan;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\DiscountPlan;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use App\Models\V2\Vpn;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
{
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

    public function testGetCollection()
    {
        $this->get(
            '/v2/discount-plans',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )->seeJson([
            'name' => $this->discountPlan->name,
            'commitment_amount' => $this->discountPlan->commitment_amount,
            'commitment_before_discount' => $this->discountPlan->commitment_before_discount,
            'discount_rate' => $this->discountPlan->discount_rate,
            'term_length' => $this->discountPlan->term_length,
        ])->assertResponseStatus(200);
    }

    public function testGetResource()
    {
        $this->get(
            '/v2/discount-plans/'.$this->discountPlan->id,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )->seeJson([
            'name' => $this->discountPlan->name,
            'commitment_amount' => $this->discountPlan->commitment_amount,
            'commitment_before_discount' => $this->discountPlan->commitment_before_discount,
            'discount_rate' => $this->discountPlan->discount_rate,
            'term_length' => $this->discountPlan->term_length,
        ])->assertResponseStatus(200);
    }
}