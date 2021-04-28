<?php

namespace Tests\V2\VpcSupport;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use GuzzleHttp\Client;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Admin\Account\AdminCustomerClient;

class CreateTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected $region;
    protected $vpc;
    protected $vpcSupport;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->region = factory(Region::class)->create();
        factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->id,
        ]);
    }

    public function testValidDataSucceeds()
    {
        $this->post(
            '/v2/support',
            [
                'vpc_id' => $this->vpc()->id,
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeInDatabase(
                'vpc_support',
                [
                    'vpc_id' => $this->vpc()->id
                ],
                'ecloud'
            )
            ->assertResponseStatus(201);
    }

    public function testCreditCardCustomerErrors()
    {
        $mockAccountAdminClient = \Mockery::mock(\UKFast\Admin\Account\AdminClient::class);
        $mockAdminCustomerClient = \Mockery::mock(\UKFast\Admin\Account\AdminCustomerClient::class)->makePartial();

        $mockAdminCustomerClient->shouldReceive('getById')->andReturn(
            new \UKFast\Admin\Account\Entities\Customer(
                [
                    'paymentMethod' => 'Credit Card'
                ]
            )
        );

        $mockAccountAdminClient->shouldReceive('customers')->andReturn(
            $mockAdminCustomerClient
        );

        app()->bind(\UKFast\Admin\Account\AdminClient::class, function () use ($mockAccountAdminClient) {
            return $mockAccountAdminClient;
        });

        $this->post(
            '/v2/support',
            [
                'vpc_id' => $this->vpc()->id,
            ],
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title' => 'Payment Required',
                'detail' => 'Payment is required before support can be enabled',
                'status' => 402
            ])
            ->assertResponseStatus(402);
    }

    public function testCreditCardCustomerErrorsAdminScoped()
    {
        $mockAccountAdminClient = \Mockery::mock(\UKFast\Admin\Account\AdminClient::class);
        $mockAdminCustomerClient = \Mockery::mock(\UKFast\Admin\Account\AdminCustomerClient::class)->makePartial();

        $mockAdminCustomerClient->shouldReceive('getById')->andReturn(
            new \UKFast\Admin\Account\Entities\Customer(
                [
                    'paymentMethod' => 'Credit Card'
                ]
            )
        );

        $mockAccountAdminClient->shouldReceive('customers')->andReturn(
            $mockAdminCustomerClient
        );

        app()->bind(\UKFast\Admin\Account\AdminClient::class, function () use ($mockAccountAdminClient) {
            return $mockAccountAdminClient;
        });

        $this->post(
            '/v2/support',
            [
                'vpc_id' => $this->vpc()->id,
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-Reseller-Id' => '1',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title' => 'Payment Required',
                'detail' => 'Payment is required before support can be enabled',
                'status' => 402
            ])
            ->assertResponseStatus(402);
    }
}
