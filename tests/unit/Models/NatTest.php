<?php

namespace Tests\unit\Models;

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
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class NatTest extends TestCase
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

        Model::withoutEvents(function () {
            $this->nat = factory(Nat::class)->create([
                'id' => 'nat-123456',
                'destination_id' => $this->floating_ip->id,
                'destinationable_type' => FloatingIp::class,
                'translated_id' => $this->nic->id,
                'translatedable_type' => Nic::class,
            ]);
        });
    }

    public function testDestinationResourceReturnsFloatingIp()
    {
        $this->assertTrue($this->nat->destination instanceof FloatingIp);
    }

    public function testTranslatedResourceReturnsNic()
    {
        $this->assertTrue($this->nat->translated instanceof Nic);
    }
}
