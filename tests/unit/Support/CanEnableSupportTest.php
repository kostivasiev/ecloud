<?php

namespace Tests\unit\Support;

use App\Http\Middleware\CanEnableSupport;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Admin\Account\AdminClient;
use UKFast\Admin\Account\AdminCustomerClient;
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

    public function testInvalidResellerId()
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
        $request = \Mockery::mock(Request::class)->makePartial();
        $request->shouldReceive('user')->andReturnSelf();
        $request->shouldReceive('isScoped')->andReturnTrue();
        $request->shouldReceive('resellerId')->andReturn(1);
        $response = $this->canEnableSupport->handle($request, function () {
            return true;
        });
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertJson(
            json_encode([
                'errors' => [
                    'title' => 'Not Found',
                    'detail' => 'The customer account is not available',
                    'status' => 403
                ]
            ]),
            $response->getContent()
        );
    }
}