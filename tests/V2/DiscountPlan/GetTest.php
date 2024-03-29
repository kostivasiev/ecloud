<?php

namespace Tests\V2\DiscountPlan;

use App\Models\V2\DiscountPlan;
use Faker\Factory as Faker;
use Tests\TestCase;

class GetTest extends TestCase
{
    protected \Faker\Generator $faker;
    protected DiscountPlan $discountPlan;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->discountPlan = DiscountPlan::factory()->create([
            'contact_id' => 1,
            'orderform_id' => '84bfdc19-977e-462b-a14b-0c4b907fff55',
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
        )->assertJsonFragment([
            'name' => $this->discountPlan->name,
            'orderform_id' => $this->discountPlan->orderform_id,
            'commitment_amount' => $this->discountPlan->commitment_amount,
            'commitment_before_discount' => $this->discountPlan->commitment_before_discount,
            'discount_rate' => $this->discountPlan->discount_rate,
            'term_length' => $this->discountPlan->term_length,
            'is_trial' => false,
        ])->assertStatus(200);
    }

    public function testGetResource()
    {
        $this->get(
            '/v2/discount-plans/'.$this->discountPlan->id,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )->assertJsonFragment([
            'name' => $this->discountPlan->name,
            'orderform_id' => $this->discountPlan->orderform_id,
            'commitment_amount' => $this->discountPlan->commitment_amount,
            'commitment_before_discount' => $this->discountPlan->commitment_before_discount,
            'discount_rate' => $this->discountPlan->discount_rate,
            'term_length' => $this->discountPlan->term_length,
            'is_trial' => false,
        ])->assertStatus(200);
    }

    public function testGetResourceIsTrial()
    {
        $discountPlan = DiscountPlan::factory()->create([
            'contact_id' => 1,
            'orderform_id' => '84bfdc19-977e-462b-a14b-0c4b907fff55',
            'is_trial' => true
        ]);

        $this->get(
            '/v2/discount-plans/' . $discountPlan->id,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )->assertJsonFragment([
            'name' => $discountPlan->name,
            'orderform_id' => $discountPlan->orderform_id,
            'commitment_amount' => $discountPlan->commitment_amount,
            'commitment_before_discount' => $discountPlan->commitment_before_discount,
            'discount_rate' => $discountPlan->discount_rate,
            'term_length' => $discountPlan->term_length,
            'is_trial' => true,
        ])->assertStatus(200);
    }
}