<?php

namespace Tests\V2\DiscountPlan;

use App\Models\V2\DiscountPlan;
use Carbon\Carbon;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class ValidationTests extends TestCase
{

    protected array $data;
    protected DiscountPlan $discountPlan;

    public function setUp(): void
    {
        parent::setUp();
        $this->data = [
            'name' => 'test-commitment',
            'commitment_amount' => 2000,
            'commitment_before_discount' => 1000,
            'discount_rate' => 5,
            'term_length' => 24,
        ];
        $this->discountPlan = factory(DiscountPlan::class)->create(
            array_merge($this->data, [
                'term_start_date' => Carbon::now()->format('Y-m-d H:i:s'),
                'term_end_date' => Carbon::now()->addDays(365)->format('Y-m-d H:i:s'),
            ])
        );
    }

    public function testCreatePlanWithAnyStartDateAsAdmin()
    {
        $data = array_merge($this->data, [
            'contact_id' => 1,
            'term_start_date' => Carbon::now()->format('Y-m-d H:i:s'),
            'term_end_date' => Carbon::now()->addDays(365)->format('Y-m-d H:i:s'),
        ]);
        $this->post(
            '/v2/discount-plans',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read, ecloud.write',
                'X-Reseller-Id' => 1,
            ]
        )->seeInDatabase(
            'discount_plans',
            $data,
            'ecloud'
        )->assertResponseStatus(201);
    }

    public function testCreatePlanWithStatusSetToApprovedAsAdmin()
    {
        $data = array_merge($this->data, [
            'contact_id' => 1,
            'status' => 'approved',
            'term_start_date' => Carbon::now()->format('Y-m-d H:i:s'),
            'term_end_date' => Carbon::now()->addDays(365)->format('Y-m-d H:i:s'),
        ]);
        $this->post(
            '/v2/discount-plans',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read, ecloud.write',
                'X-Reseller-Id' => 1,
            ]
        )->seeInDatabase(
            'discount_plans',
            $data,
            'ecloud'
        )->assertResponseStatus(201);

        $planId = json_decode($this->response->getContent())->data->id;
        $discountPlan = DiscountPlan::findOrFail($planId);
        $this->assertEquals($data['status'], $discountPlan->status);
    }

    public function testCreatePlanWithoutResellerScopingAsAdmin()
    {
        $data = array_merge($this->data, [
            'contact_id' => 1,
            'term_start_date' => Carbon::now()->format('Y-m-d H:i:s'),
            'term_end_date' => (Carbon::now())->addDays(365)->format('Y-m-d H:i:s'),
        ]);
        $this->post(
            '/v2/discount-plans',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read, ecloud.write',
            ]
        )->seeJson([
            'title' => 'No Reseller Specified',
            'detail' => 'A reseller id has not been specified.',
        ])->assertResponseStatus(422);
    }

    public function testAdminUpdatesDiscountPlanWithAnyStartDate()
    {
        // A year ago
        $data = [
            'contact_id' => 1,
            'term_start_date' => Carbon::now()->subDays(365)->format('Y-m-d H:i:s')
        ];
        $this->patch(
            '/v2/discount-plans/'.$this->discountPlan->id,
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read, ecloud.write',
                'X-Reseller-Id' => 1,
            ]
        )->seeInDatabase(
            'discount_plans',
            [
                'id' => $this->discountPlan->id,
                'term_start_date' => $data['term_start_date']
            ],
            'ecloud'
        )->assertResponseStatus(200);


        // In a year
        $data = [
            'contact_id' => 1,
            'term_start_date' => Carbon::now()->addDays(365)->format('Y-m-d H:i:s')
        ];
        $this->patch(
            '/v2/discount-plans/'.$this->discountPlan->id,
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read, ecloud.write',
                'X-Reseller-Id' => 1,
            ]
        )->seeInDatabase(
            'discount_plans',
            [
                'id' => $this->discountPlan->id,
                'term_start_date' => $data['term_start_date']
            ],
            'ecloud'
        )->assertResponseStatus(200);
    }

    public function testUserCreatesAPlanForToday()
    {
        $data = array_merge($this->data, [
            'term_start_date' => Carbon::now()->format('Y-m-d H:i:s'),
            'term_end_date' => Carbon::now()->addDays(365)->format('Y-m-d H:i:s'),
        ]);
        $this->post(
            '/v2/discount-plans',
            $data,
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.read, ecloud.write',
            ]
        )->seeInDatabase(
            'discount_plans',
            $data,
            'ecloud'
        )->assertResponseStatus(201);
    }

    public function testUserCreatesAPlanStartingNextWeek()
    {
        $data = array_merge($this->data, [
            'term_start_date' => (Carbon::now())->addDays(7)->format('Y-m-d H:i:s'),
            'term_end_date' => (Carbon::now())->addDays(365)->format('Y-m-d H:i:s'),
        ]);
        $this->post(
            '/v2/discount-plans',
            $data,
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.read, ecloud.write',
            ]
        )->seeInDatabase(
            'discount_plans',
            $data,
            'ecloud'
        )->assertResponseStatus(201);
    }

    public function testUserCreateAPlanStartingFirstOfMonth()
    {
        $data = array_merge($this->data, [
            'term_start_date' => (Carbon::now())->addDays(7)->format('Y-m-01 H:i:s'),
            'term_end_date' => (Carbon::now())->addDays(365)->format('Y-m-d H:i:s'),
        ]);
        $this->post(
            '/v2/discount-plans',
            $data,
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.read, ecloud.write',
            ]
        )->seeInDatabase(
            'discount_plans',
            $data,
            'ecloud'
        )->assertResponseStatus(201);
    }

    public function testUserCreatePlanStartsInPastNotFirstOfMonth()
    {
        $data = array_merge($this->data, [
            'term_start_date' => (Carbon::now())->subDays(30)->format('Y-m-d H:i:s'),
            'term_end_date' => (Carbon::now())->addDays(365)->format('Y-m-d H:i:s'),
        ]);
        $this->post(
            '/v2/discount-plans',
            $data,
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.read, ecloud.write',
            ]
        )->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The term start date should be either the first of the current month or a date from today',
            'source' => 'term_start_date',
        ])->assertResponseStatus(422);
    }
}