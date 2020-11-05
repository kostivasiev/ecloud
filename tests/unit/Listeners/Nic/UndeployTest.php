<?php

namespace Tests\unit\Listeners\Nic;

use App\Events\V2\Nic\Deleted;
use App\Listeners\V2\Nic\DeleteDhcpLease;
use App\Listeners\V2\Nic\UnassignFloatingIp;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\FloatingIp;
use App\Models\V2\Instance;
use App\Models\V2\Nat;
use App\Models\V2\Network;
use App\Models\V2\Nic;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use App\Services\V2\NsxService;
use Faker\Factory as Faker;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UndeployTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected $region;
    protected $availability_zone;
    protected $vpc;
    protected $router;
    protected $network;
    protected $instance;
    protected $floating_ip;
    protected $nic;
    protected $nat;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->region = factory(Region::class)->create();
        $this->availability_zone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->id,
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->id,
        ]);
        $this->router = factory(Router::class)->create([
            'availability_zone_id' => $this->availability_zone->id,
        ]);
        $this->network = factory(Network::class)->create([
            'router_id' => $this->router->id,
        ]);
        $this->instance = factory(Instance::class)->create([
            'availability_zone_id' => $this->availability_zone->id,
            'vpc_id' => $this->vpc->id,
        ]);
        $this->floating_ip = factory(FloatingIp::class)->create([
            'ip_address' => $this->faker->ipv4,
        ]);

        Model::withoutEvents(function () {
            $this->nic = factory(Nic::class)->create([
                'id' => 'nic-abc123',
                'instance_id' => $this->instance->id,
                'network_id' => $this->network->id,
                'ip_address' => $this->faker->ipv4,
            ]);

            $this->nat = factory(Nat::class)->create([
                'id' => 'nat-123456',
                'destination_id' => $this->floating_ip->getKey(),
                'destinationable_type' => 'fip',
                'translated_id' => 'nic-abc123',
                'translatedable_type' => 'nic',
            ]);
        });
    }

    public function testDeletingNicRemovesDhcpLease()
    {
        $mockNsxService = \Mockery::mock(new NsxService(new Client(), $this->faker->uuid()))->makePartial();
        app()->bind(NsxService::class, function () use ($mockNsxService) {

            $mockNsxService->shouldReceive('delete')
                ->withArgs(['/policy/api/v1/infra/tier-1s/' . $this->router->getKey() . '/segments/' . $this->network->getKey()
                    . '/dhcp-static-binding-configs/' . $this->nic->getKey()
                ])
                ->andReturn(
                    new Response(200)
                );

            $mockNsxService->shouldReceive('get')
                ->withArgs(['policy/api/v1/infra/tier-1s/' . $this->router->getKey() . '/state'])
                ->andReturn(
                    new Response(200)
                );
            return $mockNsxService;
        });

        Event::Fake(Deleted::class);

        $listener = \Mockery::mock(DeleteDhcpLease::class)->makePartial();
        $listener->handle(new \App\Events\V2\Nic\Deleted($this->nic));
    }

    public function testDeletingNicUnassignsFloatingIps()
    {
        Event::Fake(Deleted::class);

        $this->assertNull($this->nat->deleted_at);

        $listener = \Mockery::mock(UnassignFloatingIp::class)->makePartial();

        $listener->handle(new \App\Events\V2\Nic\Deleted($this->nic));

        $this->assertNotNull($this->nat->refresh()->deleted_at);
    }
}
