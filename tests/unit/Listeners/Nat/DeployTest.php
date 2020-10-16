<?php

namespace Tests\unit\Listeners\Nat;

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
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeployTest extends TestCase
{
    use DatabaseMigrations;

    protected $region;
    protected $availability_zone;
    protected $vpc;
    protected $router;
    protected $network;
    protected $instance;
    protected $floating_ip;
    protected $nic;

    public function setUp(): void
    {
        parent::setUp();

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
        $this->floating_ip = factory(FloatingIp::class)->create();
        $this->nic = factory(Nic::class)->create([
            'instance_id' => $this->instance->id,
            'network_id' => $this->network->id,
        ]);

        Model::withoutEvents(function () {
            $this->nat = factory(Nat::class)->create([
                'id' => 'nat-123456',
                'destination' => $this->floating_ip->id,
                'translated' => $this->nic->id,
            ]);
        });
    }

    public function testUpdatingNatWithoutEditingRulesDoesNotDeploy()
    {
        $listener = \Mockery::mock();
        $listener->shouldReceive('delete')
            ->never();
        $listener->shouldReceive('patch')
            ->never();
        app()->bind(NsxService::class, function () use ($listener) {
            return $listener;
        });
        $this->nat->save();
    }

    public function testUpdatingNatRemovesOldRuleAndAddsNewRule()
    {
        $listener = \Mockery::mock();
        $listener->shouldReceive('delete')
            ->once()
            ->andReturn(new Response(200)); // TODO :- Build on this
        $listener->shouldReceive('patch')
            ->once()
            ->andReturn(new Response(200)); // TODO :- Build on this
        app()->bind(NsxService::class, function () use ($listener) {
            return $listener;
        });

        $newFloatingIp = factory(FloatingIp::class)->create();
        $this->nat->destination = $newFloatingIp->id;
        $this->nat->save();
    }
}
