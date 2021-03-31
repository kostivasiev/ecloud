<?php

namespace Tests\unit\Listeners\V2\Nat;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\FloatingIp;
use App\Models\V2\Instance;
use App\Models\V2\Nat;
use App\Models\V2\Network;
use App\Models\V2\Nic;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeployTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected $instance;
    protected $floating_ip;
    protected $nic;
    protected $nat;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->instance = factory(Instance::class)->create([
            'availability_zone_id' => $this->availabilityZone()->id,
            'vpc_id' => $this->vpc()->id,
        ]);
        $this->floating_ip = FloatingIp::withoutEvents(function () {
            return factory(FloatingIp::class)->create([
                'id' => 'fip-test',
                'ip_address' => $this->faker->ipv4,
            ]);
        });

        $this->nsxServiceMock()->expects('put')
            ->withSomeOfArgs('/policy/api/v1/infra/tier-1s/' . $this->router()->id . '/segments/' . $this->network()->id . '/dhcp-static-binding-configs/nic-a1ae98ce')
            ->andReturnUsing(function () {
                return new Response(200);
            });
        $this->nic = factory(Nic::class)->create([
            'id' => 'nic-a1ae98ce',
            'instance_id' => $this->instance->id,
            'network_id' => $this->network()->id,
            'ip_address' => $this->faker->ipv4,
        ]);

        $this->nat = factory(Nat::class)->create([
            'destination_id' => $this->floating_ip->id,
            'destinationable_type' => FloatingIp::class,
            'translated_id' => $this->nic()->id,
            'translatedable_type' => Nic::class,
        ]);
    }

    public function testUpdatingNatWithoutEditingRulesDoesNotDeploy()
    {
        $this->nsxServiceMock()->shouldReceive('patch')
            ->never();
        $this->nat->save();

        // Hack so the test doesn't throw "This test did not perform any assertions"
        $this->addToAssertionCount(1);
    }

    public function testUpdatingNatRemovesOldRuleAndAddsNewRule()
    {
        $newFloatingIp = FloatingIp::withoutEvents(function () {
            return factory(FloatingIp::class)->create([
                'id' => 'fip-another',
                'ip_address' => $this->faker->ipv4,
            ]);
        });
        $this->nat->destination_id = $newFloatingIp->id;

        $this->nsxServiceMock()->shouldReceive('patch')
            ->once()
            ->andReturn(new Response(200)); // TODO :- Build on this

        $listener = \Mockery::mock(\App\Listeners\V2\Nat\Deploy::class)->makePartial();
        $listener->handle(new \App\Events\V2\Nat\Saved($this->nat));

        $this->nat->save();
        Event::assertDispatched(\App\Events\V2\Nat\Saving::class, function ($event) {
            return $event->model->id === $this->nat->id;
        });
        Event::assertDispatched(\App\Events\V2\Nat\Saved::class, function ($event) {
            return $event->model->id === $this->nat->id;
        });
    }
}
