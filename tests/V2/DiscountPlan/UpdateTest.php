<?php

namespace Tests\V2\DiscountPlan;

use App\Models\V2\DiscountPlan;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    protected DiscountPlan $discountPlan;

    public function setUp(): void
    {
        parent::setUp();
        $this->discountPlan = factory(DiscountPlan::class)->create([
            'contact_id' => 1,
            'name' => 'test-commitment',
            'commitment_amount' => '2000',
            'commitment_before_discount' => '1000',
            'discount_rate' => '5',
            'term_length' => '24',
            'term_start_date' => date('Y-m-d 00:00:00', strtotime('now')),
            'term_end_date' => date('Y-m-d 00:00:00', strtotime('2 days')),
        ]);
    }

    public function testUpdateItem()
    {
        $data = [
            'name' => 'updated-test-commitment',
            'commitment_amount' => '3000',
            'commitment_before_discount' => '2000',
            'discount_rate' => '10',
            'term_length' => '36',
            'term_start_date' => date('Y-m-d', strtotime('tomorrow')),
            'term_end_date' => date('Y-m-d', strtotime('4 days')),
        ];
        $this->patch(
            '/v2/discount-plans/'.$this->discountPlan->id,
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read, ecloud.write',
                'X-Reseller-Id' => 1,
            ]
        )->assertResponseStatus(200);

        $endDate = date(
            'Y-m-d',
            strtotime(
                '+'.$data['term_length'].' months',
                strtotime($data['term_start_date'])
            )
        );
        $plan = DiscountPlan::findOrFail($this->discountPlan->id);
        $this->assertEquals($data['name'], $plan->name);
        $this->assertEquals($data['commitment_amount'], $plan->commitment_amount);
        $this->assertEquals($data['commitment_before_discount'], $plan->commitment_before_discount);
        $this->assertEquals($data['discount_rate'], $plan->discount_rate);
        $this->assertEquals($data['term_length'], $plan->term_length);
        $this->assertEquals($data['term_start_date'], $plan->term_start_date->format('Y-m-d'));
        $this->assertEquals($endDate, $plan->term_end_date->format('Y-m-d'));
    }

    public function testUpdateItemInvalid()
    {
        $data = [
            'name' => 'updated-test-commitment',
            'commitment_amount' => '3000',
            'commitment_before_discount' => '2000',
            'discount_rate' => '10',
            'term_length' => '36',
            'term_start_date' => date('Y-m-d 00:00:00', strtotime('tomorrow')),
            'term_end_date' => date('Y-m-d 00:00:00', strtotime('tomorrow')),
        ];
        $this->patch(
            '/v2/discount-plans/'.$this->discountPlan->id,
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read, ecloud.write',
                'X-Reseller-Id' => 1,
            ]
        )->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The term_end_date must be greater than the term_start_date',
        ])->assertResponseStatus(422);
    }
}
