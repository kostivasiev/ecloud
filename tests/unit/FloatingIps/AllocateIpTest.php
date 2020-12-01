<?php

namespace Tests\unit\FloatingIps;

use App\Events\V2\FloatingIp\Created;
use App\Events\V2\Nat\Saved;
use App\Jobs\AvailabilityZoneCapacity\UpdateFloatingIpCapacity;
use App\Listeners\V2\FloatingIp\AllocateIp;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\FloatingIp;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class AllocateIpTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;

    protected $region;
    protected $availability_zone;
    protected $vpc;
    protected $instance;
    protected $floating_ip;
    protected $nic;
    protected $listener;
    protected $event;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->region = factory(Region::class)->create();
        $this->availability_zone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->floating_ip = factory(FloatingIp::class)->create([
            'ip_address' => null,
            'vpc_id' => $this->vpc->getKey()
        ]);

        $this->event = new Created($this->floating_ip);

        $mockAdminNetworking = \Mockery::mock(\UKFast\Admin\Networking\AdminClient::class)
            ->shouldAllowMockingProtectedMethods();
        app()->bind(\UKFast\Admin\Networking\AdminClient::class, function () use ($mockAdminNetworking) {
            $mockAdminNetworking->shouldReceive('ipRanges->getPage->totalPages')->andReturn(1);

            $mockAdminNetworking->shouldReceive('ipRanges->getPage->getItems')->andReturn(
                new Collection([
                    new \UKFast\Admin\Networking\Entities\IpRange(
                        [
                            "id" => 9028,
                            "description" => "203.0.113.0\/24 TEST-NET-3",
                            "externalSubnet" => "255.255.255.0",
                            "internalSubnet" => "",
                            "dnsOne" => "",
                            "dnsTwo" => "",
                            "vlan" => "",
                            "ipv6" => null,
                            "ipv6Subnet" => "",
                            "ipv6Gateway" => "",
                            "ipv6DnsOne" => "",
                            "ipv6DnsTwo" => "",
                            "autoDeployEnvironment" => "ecloud nsx",
                            "autoDeployFirewall_Id" => 0,
                            "autoDeployDatacentreId" => 8,
                            "resellerId" => 0,
                            "parentRangeId" => 0,
                            "networkAddress" => 3405803776,
                            "cidr" => 24,
                            "type" => "External",
                            "vrfNumber" => 0
                        ]
                    )
                ])
            );
            return $mockAdminNetworking;
        });

        $this->listener = \Mockery::mock(AllocateIp::class)->makePartial();
    }

    public function testAllocateIp()
    {
        $this->listener->handle($this->event);
        $this->floating_ip->refresh();
        $this->assertEquals($this->floating_ip->ip_address, '203.0.113.1');
    }

    public function testAllocateNextIp()
    {
        Event::Fake(Created::class);

        // Assign the first available IP, 203.0.113.1
        $this->listener->handle($this->event);

        // Check assigning the next
        $floatingIp = factory(FloatingIp::class, 1)->create([
            'ip_address' => null,
        ])->each(function ($fip) {
            $vpc = factory(Vpc::class)->create([
                'region_id' => $this->region->getKey()
            ]);
            $fip->vpc_id = $vpc->getKey();
            $fip->save();
        })->first();

        $event = new Created($floatingIp);

        $this->listener->handle($event);
        $floatingIp->refresh();

        $this->assertEquals($floatingIp->ip_address, '203.0.113.2');
    }

    public function testDeletedIpAvailableAgain()
    {
        Event::Fake(Created::class);

        // Assign the first available IP, 203.0.113.1
        $this->listener->handle($this->event);
        $this->floating_ip->refresh();
        $this->assertEquals($this->floating_ip->ip_address, '203.0.113.1');
        $this->floating_ip->delete();

        // Check assigning the IP again
        $floatingIp = factory(FloatingIp::class, 1)->create([
            'ip_address' => null,
        ])->each(function ($fip) {
            $vpc = factory(Vpc::class)->create([
                'region_id' => $this->region->getKey()
            ]);
            $fip->vpc_id = $vpc->getKey();
            $fip->save();
        })->first();

        $event = new Created($floatingIp);

        $this->listener->handle($event);
        $floatingIp->refresh();

        $this->assertEquals($floatingIp->ip_address, '203.0.113.1');
    }
}
