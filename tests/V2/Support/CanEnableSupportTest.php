<?php

namespace Tests\V2\Support;

use App\Http\Middleware\CanEnableSupport;
use Illuminate\Http\Request;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Admin\Account\AdminClient;
use UKFast\Admin\Account\AdminCustomerClient;
use UKFast\Admin\Account\Entities\Customer;
use UKFast\Api\Auth\Consumer;

class CanEnableSupportTest extends TestCase
{
    use DatabaseMigrations;

    protected CanEnableSupport $canEnableSupport;

    public function setUp(): void
    {
        parent::setUp();
        $this->canEnableSupport = new CanEnableSupport();
    }

    public function testWithBadCustomerAccount()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->post('/v2/support', [
            'vpc_id' => $this->vpc()->id
        ])->seeJson(
            [
                'title' => 'Not Found',
                'detail' => 'The customer account is not available',
                'status' => 404,
            ]
        )->assertResponseStatus(404);
    }

    public function testWithValidCustomerAndCreditCard()
    {
        app()->bind(AdminClient::class, function () {
            $mockClient = \Mockery::mock(AdminClient::class)->makePartial();
            $mockCustomer = \Mockery::mock(AdminCustomerClient::class)->makePartial();

            $mockCustomer->shouldReceive('getById')
                ->andReturn(
                    new Customer([
                        'paymentMethod' => 'Credit Card',
                    ])
                );
            $mockClient->shouldReceive('customers')->andReturn($mockCustomer);
            return $mockClient;
        });

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->post('/v2/support', [
            'vpc_id' => $this->vpc()->id
        ])->seeJson(
            [
                'title' => 'Payment Required',
                'detail' => 'Payment is required before support can be enabled',
                'status' => 402,
            ]
        )->assertResponseStatus(402);
    }

    public function testWithValidCustomerAndAccount()
    {
        app()->bind(AdminClient::class, function () {
            $mockClient = \Mockery::mock(AdminClient::class)->makePartial();
            $mockClient->shouldReceive('customers')
                ->andReturnUsing(function () {
                    return new class {
                        public string $paymentMethod = 'Invoice';
                        public function getById($id)
                        {
                            return $this;
                        }
                    };
                });
            return $mockClient;
        });

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->post('/v2/support', [
            'vpc_id' => $this->vpc()->id
        ])->assertResponseStatus(201);
    }
}