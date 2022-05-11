<?php

namespace Tests\V2\Support;

use App\Events\V2\Task\Created;
use App\Support\Sync;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
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

            $mockCustomer->allows('getById')
                ->andThrow(
                    new ClientException(
                        'Not Found',
                        new \GuzzleHttp\Psr7\Request('GET', '/'),
                        new Response(404)
                    )
                );
            $mockClient->allows('customers')->andReturns($mockCustomer);
            return $mockClient;
        });
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->patch('/v2/vpcs/'.$this->vpc()->id, [
            'support_enabled' => true
        ])->assertJsonFragment(
            [
                'title' => 'Not Found',
                'detail' => 'The customer account is not available',
                'status' => 403,
            ]
        )->assertStatus(403);

        Event::assertNotDispatched(Created::class);
    }

    public function testWithValidCustomerAndCreditCard()
    {
        Event::fake(Created::class);

        app()->bind(AdminClient::class, function () {
            $mockClient = \Mockery::mock(AdminClient::class)->makePartial();
            $mockCustomer = \Mockery::mock(AdminCustomerClient::class)->makePartial();

            $mockCustomer->allows('getById')
                ->andReturns(
                    new Customer([
                        'paymentMethod' => 'Credit Card',
                    ])
                );
            $mockClient->allows('customers')->andReturns($mockCustomer);
            return $mockClient;
        });

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->patch('/v2/vpcs/'.$this->vpc()->id, [
            'support_enabled' => true
        ])->assertJsonFragment(
            [
                'title' => 'Payment Required',
                'detail' => 'Payment is required before support can be enabled',
                'status' => 402,
            ]
        )->assertStatus(402);

        Event::assertNotDispatched(Created::class);
    }

    public function testWithValidCustomerAndAccount()
    {
        Event::fake(Created::class);

        app()->bind(AdminClient::class, function () {
            $mockClient = \Mockery::mock(AdminClient::class)->makePartial();
            $mockCustomer = \Mockery::mock(AdminCustomerClient::class)->makePartial();

            $mockCustomer->allows('getById')
                ->andReturns(
                    new Customer([
                        'paymentMethod' => 'Invoice',
                    ])
                );
            $mockClient->allows('customers')->andReturns($mockCustomer);
            return $mockClient;
        });

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->patch('/v2/vpcs/'.$this->vpc()->id, [
            'support_enabled' => true
        ])->assertStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_UPDATE;
        });
    }
}