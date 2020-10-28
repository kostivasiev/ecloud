<?php

namespace Tests\unit\Listeners\Nat;

use App\Events\V2\Nat\Deleted;
use App\Listeners\V2\Nat\Undeploy;
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
        $this->nic = factory(Nic::class)->create([
            'instance_id' => $this->instance->id,
            'network_id' => $this->network->id,
            'ip_address' => $this->faker->ipv4,
        ]);

        Model::withoutEvents(function () {
            $this->nat = factory(Nat::class)->create([
                'id' => 'nat-123456',
                'destination_id' => $this->floating_ip->id,
                'destinationable_type' => FloatingIp::class,
                'translated_id' => $this->nic->id,
                'translatedable_type' => Nic::class,
            ]);
        });


        $this->event = new Deleted($this->nat);

        $this->listener = \Mockery::mock(Undeploy::class)->makePartial();
    }


    public function testDeletingNatRemovesRule()
    {
        $mockNsxService = \Mockery::mock(new NsxService(new Client(), $this->faker->uuid()))->makePartial();
        app()->bind(NsxService::class, function () use ($mockNsxService) {

            $mockNsxService->shouldReceive('delete')
                ->withArgs(['policy/api/v1/infra/tier-1s/' . $this->router->getKey() . '/nat/USER/nat-rules/' . $this->nat->getKey()])
                ->andReturn(
                    new Response(200)
                );
            return $mockNsxService;
        });

        Event::Fake(Deleted::class);

        $this->listener->handle($this->event);
    }
}
