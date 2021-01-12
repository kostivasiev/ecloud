<?php

namespace Tests\unit\Vpc;

use App\Events\V2\Vpc\Deleted;
use App\Events\V2\Router\Deleted as RouterDeleted;
use App\Listeners\V2\Vpc\Routers\Delete as DeleteRouters;
use App\Listeners\V2\Vpc\FloatingIps\Delete as DeleteFloatingIps;
use App\Listeners\V2\Router\Networks\Delete as DeleteNetworks;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Credential;
use App\Models\V2\Dhcp;
use App\Models\V2\FloatingIp;
use App\Models\V2\Network;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use App\Providers\EncryptionServiceProvider;
use App\Services\V2\NsxService;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteRouterTest extends TestCase
{
    use DatabaseMigrations;

    protected AvailabilityZone $availabilityZone;
    protected Dhcp $dhcp;
    protected FloatingIp $floatingIp;
    protected Network $network;
    protected Region $region;
    protected Router $router;
    protected Vpc $vpc;

    protected function setUp(): void
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
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
        factory(Credential::class)->create([
            'name' => 'NSX',
            'resource_id' => $this->availabilityZone->getKey(),
        ]);
        $this->dhcp = factory(Dhcp::class)->create([
            'vpc_id' => $this->vpc->getKey(),
            'availability_zone_id' => $this->availabilityZone->getKey(),
        ]);
        $this->router = factory(Router::class)->create([
            'vpc_id' => $this->vpc->getKey(),
        ]);
        $this->network = factory(Network::class)->create([
            'router_id' => $this->router->getKey(),
        ]);
        $this->floatingIp = factory(FloatingIp::class)->create([
            'vpc_id' => $this->vpc->getKey(),
        ]);

        $nsxService = app()->makeWith(NsxService::class, [$this->availabilityZone]);
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

    public function testDeletingVpcDeletesRouter()
    {
        Event::fake(Deleted::class);
        $listener = \Mockery::mock(DeleteRouters::class)->makePartial();
        $listener->handle(new Deleted($this->vpc));
        $this->router->refresh();
        $this->assertNotNull($this->router->deleted_at);
    }

    public function testDeletingVpcDeletesFloatingIp()
    {
        Event::fake(Deleted::class);
        $listener = \Mockery::mock(DeleteFloatingIps::class)->makePartial();
        $listener->handle(new Deleted($this->vpc));
        $this->floatingIp->refresh();
        $this->assertNotNull($this->floatingIp->deleted_at);
    }

    public function testDeletingVpcDeletesRouterNetwork()
    {
        Event::fake(RouterDeleted::class);
        $listener = \Mockery::mock(DeleteNetworks::class)->makePartial();
        $listener->handle(new RouterDeleted($this->router));
        $this->network->refresh();
        $this->assertNotNull($this->network->deleted_at);
    }
}