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

    protected $availability_zone;
    protected $region;
    protected $router;
    protected $vpc;

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
        $this->availability_zone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->id,
        ]);
        factory(Credential::class)->create([
            'name' => 'NSX',
            'resource_id' => $this->availability_zone->id,
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->id,
        ]);
        $this->router = factory(Router::class)->create([
            'vpc_id' => $this->vpc->id,
            'availability_zone_id' => $this->availability_zone->id,
        ]);

        $nsxService = app()->makeWith(NsxService::class, [$this->availability_zone]);
        $mockNsxService = \Mockery::mock($nsxService)->makePartial();
        app()->bind(NsxService::class, function () use ($mockNsxService) {
            $mockNsxService->shouldReceive('get')->withArgs([
                'policy/api/v1/infra/tier-1s/' . $this->router->id . '/state'
            ])->andReturn(
                new Response(200, [], json_encode(['tier1_state' => ['state' => 'in_sync']])),
                new Response(200, [], json_encode(['tier1_state' => ['state' => 'in_sync']])),
                new Response(200, [], json_encode(['tier1_state' => ['state' => 'in_sync']])),
                new Response(200, [], json_encode(['tier1_state' => ['state' => 'in_sync']])),
                new Response(200, [], json_encode(['tier1_state' => ['state' => 'in_sync']])),
                new Response(200, [], json_encode(['tier1_state' => ['state' => 'in_sync']]))
            );
            return $mockNsxService;
        });
    }

    public function testSuccessfulDelete()
    {
        $this->assertNull($this->router->deleted_at);
        $this->delete('/v2/routers/' . $this->router->id, [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(204);
        $this->router->refresh();
        $this->assertNotNull($this->router->deleted_at);
    }
}
