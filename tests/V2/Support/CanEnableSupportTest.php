<?php

namespace Tests\V2\Support;

use App\Http\Middleware\CanEnableSupport;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Admin\Account\AdminClient;
use UKFast\Admin\Account\AdminCustomerClient;
use UKFast\Admin\Account\Entities\Customer;
use UKFast\Api\Auth\Consumer;

class CanEnableSupportTest extends TestCase
{
    protected CanEnableSupport $canEnableSupport;

    public function setUp(): void
    {
        parent::setUp();
        $this->canEnableSupport = new CanEnableSupport();
    }

    public function testWithBadCustomerAccount()
    {
        app()->bind(AdminClient::class, function () {
            $mockClient = \Mockery::mock(AdminClient::class)->makePartial();
            $mockCustomer = \Mockery::mock(AdminCustomerClient::class)->makePartial();

            $mockCustomer->shouldReceive('getById')
                ->andThrow(
                    new ClientException(
                        'Not Found',
                        new \GuzzleHttp\Psr7\Request('GET', '/'),
                        new Response(404)
                    )
                );
            $mockClient->shouldReceive('customers')->andReturn($mockCustomer);
            return $mockClient;
        });
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->post('/v2/support', [
            'vpc_id' => $this->vpc()->id
        ])->seeJson(
            [
                'title' => 'Not Found',
                'detail' => 'The customer account is not available',
                'status' => 403,
            ]
        )->assertResponseStatus(403);
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
            $mockCustomer = \Mockery::mock(AdminCustomerClient::class)->makePartial();

            $mockCustomer->shouldReceive('getById')
                ->andReturn(
                    new Customer([
                        'paymentMethod' => 'Invoice',
                    ])
                );
            $mockClient->shouldReceive('customers')->andReturn($mockCustomer);
            return $mockClient;
        });

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->post('/v2/support', [
            'vpc_id' => $this->vpc()->id
        ])->assertResponseStatus(201);
    }
}