<?php

namespace Tests\V2\DiscountPlan;

use App\Models\V2\DiscountPlan;
use Tests\TestCase;

class CreateTest extends TestCase
{

    public function testCreateItem()
    {
        $data = [
            'contact_id' => 1,
            'name' => 'test-commitment',
            'commitment_amount' => '2000',
            'commitment_before_discount' => '1000',
            'discount_rate' => '5',
            'term_length' => '24',
            'term_start_date' => date('Y-m-d 00:00:00', strtotime('now')),
            'term_end_date' => date('Y-m-d 00:00:00', strtotime('2 days')),
        ];
        $this->post(
            '/v2/discount-plans',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read, ecloud.write',
                'X-Reseller-Id' => 1,
            ]
        )->assertStatus(201);

        $this->assertDatabaseHas(
            'discount_plans',
            $data,
            'ecloud'
        );
    }

    public function testCreateItemAutoCalcEndDate()
    {
        $data = [
            'contact_id' => 1,
            'orderform_id' => '84bfdc19-977e-462b-a14b-0c4b907fff55',
            'name' => 'test-commitment',
            'commitment_amount' => '2000',
            'commitment_before_discount' => '1000',
            'discount_rate' => '5',
            'term_length' => '24',
            'term_start_date' => date('Y-m-d 00:00:00', strtotime('now')),
        ];
        $post = $this->post(
            '/v2/discount-plans',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read, ecloud.write',
                'X-Reseller-Id' => 1,
            ]
        )->assertStatus(201);

        $this->assertDatabaseHas(
            'discount_plans',
            $data,
            'ecloud'
        );

        $planId = (json_decode($post->getContent()))->data->id;
        $planEndDate = date(
            'Y-m-d',
            strtotime('+'.$data['term_length'].' months', strtotime($data['term_start_date']))
        );

        $discountPlan = DiscountPlan::findOrFail($planId);
        $this->assertEquals($planEndDate, $discountPlan->term_end_date->format('Y-m-d'));
    }

    public function testCreateItemNoEndDate()
    {
        $data = [
            'contact_id' => 1,
            'orderform_id' => '84bfdc19-977e-462b-a14b-0c4b907fff55',
            'name' => 'test-commitment',
            'commitment_amount' => '2000',
            'commitment_before_discount' => '1000',
            'discount_rate' => '5',
            'term_start_date' => date('Y-m-d 00:00:00', strtotime('now')),
        ];
        $post = $this->post(
            '/v2/discount-plans',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read, ecloud.write',
                'X-Reseller-Id' => 1,
            ]
        )->assertStatus(201);

        $this->assertDatabaseHas(
            'discount_plans',
            $data,
            'ecloud'
        );

        $planId = (json_decode($post->getContent()))->data->id;

        $discountPlan = DiscountPlan::findOrFail($planId);
        $this->assertNull($discountPlan->term_end_date);
    }

    public function testCreateItemInvalid()
    {
        $data = [
            'contact_id' => 1,
            'orderform_id' => '84bfdc19-977e-462b-a14b-0c4b907fff55',
            'commitment_amount' => '2000',
            'commitment_before_discount' => '1000',
            'discount_rate' => '5',
            'term_length' => '24',
            'term_start_date' => date('Y-m-d', strtotime('now')),
            'term_end_date' => date('Y-m-d', strtotime('now')),
        ];
        $this->post(
            '/v2/discount-plans',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read, ecloud.write',
                'X-Reseller-Id' => 1,
            ]
        )->assertJsonFragment([
            'title' => 'Validation Error',
            'detail' => 'The term_end_date must be greater than the term_start_date',
        ])->assertStatus(422);
    }
}
