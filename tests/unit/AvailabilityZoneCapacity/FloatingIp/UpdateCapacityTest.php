<?php

namespace Tests\unit\AvailabilityZoneCapacity\FloatingIp;

use App\Listeners\V2\FloatingIp\AllocateIp;
use App\Mail\AvailabilityZoneCapacityAlert;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\AvailabilityZoneCapacity;
use App\Models\V2\FloatingIp;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateCapacityTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;

    protected $region;
    protected $availabilityZone;
    protected $vpc;
    protected $floatingIp;
    protected $availabilityZoneCapacity;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->region = factory(Region::class)->create();
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->floatingIp = factory(FloatingIp::class)->create([
            'ip_address' => '1.1.1.1',
            'vpc_id' => $this->vpc->getKey()
        ]);

        $this->availabilityZoneCapacity = factory(AvailabilityZoneCapacity::class)->create([
            'availability_zone_id' => $this->availabilityZone->getKey(),
            'current' => null
        ]);

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
    }

    public function testCapacityUpdatedWhenFloatingIpCreated()
    {
        $this->assertEquals(null, $this->availabilityZoneCapacity->current);

        $allocateIpListener = \Mockery::mock(AllocateIp::class)->makePartial();

        $allocateIpListener->handle(new \App\Events\V2\FloatingIp\Created($this->floatingIp));

        Event::assertDispatched(\App\Events\V2\AvailabilityZoneCapacity\Saved::class, function ($event) {
            return $event->model->id === $this->availabilityZoneCapacity->getKey();
        });

        $this->availabilityZoneCapacity->refresh();

        $this->assertNotNull($this->availabilityZoneCapacity->current);
        $this->assertIsNumeric($this->availabilityZoneCapacity->current);
    }

    public function testCapacityUpdatedPercentageCalculation()
    {
        // /24 netmask for TEST-NET-3 range should give us 254 usable addresses.
        $totalIps = 254;
        $usedIps = 1;

        $percentUsed = round(($usedIps / $totalIps) * 100, 2); // 0.39

        $allocateIpListener = \Mockery::mock(AllocateIp::class)->makePartial();

        $allocateIpListener->handle(new \App\Events\V2\FloatingIp\Created($this->floatingIp));

        Event::assertDispatched(\App\Events\V2\AvailabilityZoneCapacity\Saved::class, function ($event) {
            return $event->model->id === $this->availabilityZoneCapacity->getKey();
        });

        $this->availabilityZoneCapacity->refresh();

        $this->assertEquals($percentUsed, $this->availabilityZoneCapacity->current);
    }

    public function testCapacityUpdateNoAlertIsTriggered()
    {
        Mail::fake();

        $this->availabilityZoneCapacity->current = 10;
        $this->availabilityZoneCapacity->save();

        $sendAlertListener = \Mockery::mock(\App\Listeners\V2\AvailabilityZoneCapacity\SendAlert::class)->makePartial();
        $sendAlertListener->handle(new \App\Events\V2\AvailabilityZoneCapacity\Saved($this->availabilityZoneCapacity));

        Mail::assertNothingSent();
    }

    public function testCapacityUpdateWarningAlertIsTriggered()
    {
        Mail::fake();

        $this->availabilityZoneCapacity->current = 70;
        $this->availabilityZoneCapacity->save();

        $sendAlertListener = \Mockery::mock(\App\Listeners\V2\AvailabilityZoneCapacity\SendAlert::class)->makePartial();
        $sendAlertListener->handle(new \App\Events\V2\AvailabilityZoneCapacity\Saved($this->availabilityZoneCapacity));

        Mail::assertSent(AvailabilityZoneCapacityAlert::class, function ($alert) {
            return $alert->alertLevel = AvailabilityZoneCapacityAlert::ALERT_LEVEL_WARNING;
        });
    }

    public function testCapacityUpdateCriticalAlertIsTriggered()
    {
        Mail::fake();

        $this->availabilityZoneCapacity->current = 85;
        $this->availabilityZoneCapacity->save();

        $sendAlertListener = \Mockery::mock(\App\Listeners\V2\AvailabilityZoneCapacity\SendAlert::class)->makePartial();
        $sendAlertListener->handle(new \App\Events\V2\AvailabilityZoneCapacity\Saved($this->availabilityZoneCapacity));

        Mail::assertSent(AvailabilityZoneCapacityAlert::class, function ($alert) {
            return $alert->alertLevel = AvailabilityZoneCapacityAlert::ALERT_LEVEL_CRITICAL;
        });
    }
}
