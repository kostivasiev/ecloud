<?php

namespace Tests\V2\Network;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Credential;
use App\Models\V2\Network;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use App\Providers\EncryptionServiceProvider;
use App\Services\V2\NsxService;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;

    protected $region;
    protected $availabilityZone;
    protected $vpc;
    protected $router;
    protected $network;

    public function setUp(): void
    {
        parent::setUp();

        $mockEncryptionServiceProvider = \Mockery::mock(EncryptionServiceProvider::class)
            ->shouldAllowMockingProtectedMethods();
        app()->bind('encrypter', function () use ($mockEncryptionServiceProvider) {
            $mockEncryptionServiceProvider->shouldReceive('encrypt')->andReturn('EnCrYpTeD-pAsSwOrD');
            $mockEncryptionServiceProvider->shouldReceive('decrypt')->andReturn('somepassword');
            return $mockEncryptionServiceProvider;
        });

        $this->region = factory(Region::class)->create();
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->id,
        ]);
        factory(Credential::class)->create([
            'name' => 'NSX',
            'resource_id' => $this->availabilityZone->id,
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->id,
        ]);
        $this->router = factory(Router::class)->create([
            'vpc_id' => $this->vpc->id,
            'availability_zone_id' => $this->availabilityZone->id
        ]);
        $this->network = factory(Network::class)->create([
            'name' => 'Manchester Network',
            'router_id' => $this->router->id,
        ]);
        $nsxService = app()->makeWith(NsxService::class, [$this->availabilityZone]);
        $mockNsxService = \Mockery::mock($nsxService)->makePartial();
        app()->bind(NsxService::class, function () use ($mockNsxService) {
            $mockNsxService->shouldReceive('delete')
                ->andReturnUsing(function () {
                    return new Response(200, [], '');
                });
            $mockNsxService->shouldReceive('get')
                ->andReturnUsing(function () {
                    return new Response(200, [], json_encode(['results' => [['id' => 0]]]));
                });
            return $mockNsxService;
        });
    }

    public function testNoPermsIsDenied()
    {
        $this->delete(
            '/v2/networks/' . $this->network->id,
            [],
            []
        )
            ->seeJson([
                'title' => 'Unauthorized',
                'detail' => 'Unauthorized',
                'status' => 401,
            ])
            ->assertResponseStatus(401);
    }

    public function testFailInvalidId()
    {
        $this->delete(
            '/v2/networks/NOT_FOUND',
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title' => 'Not found',
                'detail' => 'No Network with that ID was found',
                'status' => 404,
            ])
            ->assertResponseStatus(404);
    }

    public function testSuccessfulDelete()
    {
        $this->delete(
            '/v2/networks/' . $this->network->id,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);
        $network = Network::withTrashed()->findOrFail($this->network->id);
        $this->assertNotNull($network->deleted_at);
    }
}
