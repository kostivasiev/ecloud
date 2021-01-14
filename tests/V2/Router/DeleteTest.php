<?php

namespace Tests\V2\Router;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Credential;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use App\Providers\EncryptionServiceProvider;
use App\Services\V2\NsxService;
use Faker\Factory as Faker;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected $availability_zone;
    protected $region;
    protected $router;
    protected $vpc;

    public function setUp(): void
    {
        parent::setUp();

        $this->faker = Faker::create();

        $mockEncryptionServiceProvider = \Mockery::mock(EncryptionServiceProvider::class)
            ->shouldAllowMockingProtectedMethods();
        app()->bind('encrypter', function () use ($mockEncryptionServiceProvider) {
            $mockEncryptionServiceProvider->shouldReceive('encrypt')->andReturn('EnCrYpTeD-pAsSwOrD');
            $mockEncryptionServiceProvider->shouldReceive('decrypt')->andReturn('somepassword');
            return $mockEncryptionServiceProvider;
        });

        $this->region = factory(Region::class)->create();
        $this->availability_zone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
        factory(Credential::class)->create([
            'name' => 'NSX',
            'resource_id' => $this->availability_zone->getKey(),
        ]);

        $nsxService = app()->makeWith(NsxService::class, [$this->availability_zone]);
        $mockNsxService = \Mockery::mock($nsxService)->makePartial();
        app()->bind(NsxService::class, function () use ($mockNsxService) {
            $result = new Response(200, [], json_encode(['tier1_state' => ['state' => 'in_sync']]));
            $mockNsxService->shouldReceive('get')->andReturn($result);
            return $mockNsxService;
        });
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
        $this->router = factory(Router::class)->create([
            'vpc_id' => $this->vpc->getKey(),
        ]);
    }

    public function testSuccessfulDelete()
    {
        $this->delete(
            '/v2/routers/' . $this->router->getKey(),
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);
        $routerItem = Router::withTrashed()->findOrFail($this->router->getKey());
        $this->assertNotNull($routerItem->deleted_at);
    }

}
