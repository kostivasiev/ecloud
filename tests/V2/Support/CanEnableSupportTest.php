<?php

namespace Tests\V2\Support;

use App\Events\V2\Task\Created;
use App\Http\Middleware\CanEnableSupport;
use App\Support\Sync;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Admin\Account\AdminClient;
use UKFast\Admin\Account\AdminCustomerClient;
use UKFast\Admin\Account\Entities\Customer;
use UKFast\Api\Auth\Consumer;

class CanEnableSupportTest extends TestCase
{
    public function testWithBadCustomerAccount()
    {
        Event::fake(Created::class);

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
        $this->patch('/v2/vpcs/'.$this->vpc()->id, [
            'support_enabled' => true
        ])->seeJson(
            [
                'title' => 'Validation Error',
                'detail' => 'Customer not found or payment required',
                'status' => 422,
            ]
        )->assertResponseStatus(422);

        Event::assertNotDispatched(Created::class);
    }

    public function testWithValidCustomerAndCreditCard()
    {
        Event::fake(Created::class);

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
        $this->patch('/v2/vpcs/'.$this->vpc()->id, [
            'support_enabled' => true
        ])->seeJson(
            [
                'title' => 'Validation Error',
                'detail' => 'Customer not found or payment required',
                'status' => 422,
            ]
        )->assertResponseStatus(422);

        Event::assertNotDispatched(Created::class);
    }

    public function testWithValidCustomerAndAccount()
    {
        Event::fake(Created::class);

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
        $this->patch('/v2/vpcs/'.$this->vpc()->id, [
            'support_enabled' => true
        ])->assertResponseStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_UPDATE;
        });
    }
}