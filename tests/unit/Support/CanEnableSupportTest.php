<?php

namespace Tests\unit\Support;

use App\Http\Middleware\CanEnableSupport;
use Illuminate\Http\Request;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Admin\Account\AdminClient;
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
        $request = \Mockery::mock(Request::class)->makePartial();
        $request->shouldReceive('user')->andReturnSelf();
        $request->shouldReceive('isScoped')->andReturnTrue();
        $request->shouldReceive('resellerId')->andReturn(1);
        $response = $this->canEnableSupport->handle($request, function () {
            return true;
        });
        $this->assertEquals(503, $response->getStatusCode());
        $this->assertJson(
            json_encode([
                'errors' => [
                    'title' => 'Not Found',
                    'detail' => 'The customer account is not available',
                    'status' => 404
                ]
            ]),
            $response->getContent()
        );
    }
}