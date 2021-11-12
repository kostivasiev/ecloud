<?php

namespace Tests\unit\Middleware\Instance;

use App\Http\Middleware\Instance\RequiresFloatingIp;
use App\Models\V2\ImageMetadata;
use App\Models\V2\Instance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class RequiresFloatingIpTest extends TestCase
{
    use LoadBalancerMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->be((new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(false));
    }

    public function testRequiresFloatingIpFails()
    {
        factory(ImageMetadata::class)->create([
            'key' => 'ukfast.fip.required',
            'value' => 'true',
            'image_id' => $this->image()->id
        ]);

        $request = Request::create(
            'POST',
            '/v2/instances',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([
                'image_id' => $this->image()->id
            ]));

        $middleware = new RequiresFloatingIp();

        $response = $middleware->handle($request, function () {});

        $this->assertEquals($response->getStatusCode(), 422);
    }

    public function testFipNotRequiredPasses()
    {
        $request = Request::create(
            'POST',
            '/v2/instances',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([
                'image_id' => $this->image()->id
            ]));

        $middleware = new RequiresFloatingIp();

        $response = $middleware->handle($request, function () {});

        $this->assertNull($response);
    }
}
