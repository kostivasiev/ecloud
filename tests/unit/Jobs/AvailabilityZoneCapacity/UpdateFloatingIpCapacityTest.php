<?php

namespace Tests\unit\Jobs\AvailabilityZoneCapacity;

use App\Jobs\AvailabilityZoneCapacity\UpdateFloatingIpCapacity;
use App\Jobs\FloatingIp\AllocateIp;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\AvailabilityZoneCapacity;
use App\Models\V2\FloatingIp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateFloatingIpCapacityTest extends TestCase
{
    use DatabaseMigrations;

    protected FloatingIp $floatingIp;
    protected $mockNetworkAdminClient;
    protected $mockAdminIpRangeClient;
    protected $availabilityZoneCapacity;

    public function setUp(): void
    {
        parent::setUp();

        //$this->mockAdminClient = \Mockery::mock(\UKFast\Admin\Devices\AdminClient::class);
        $this->mockNetworkAdminClient = \Mockery::mock(\UKFast\Admin\Networking\AdminClient::class);
        $this->mockAdminIpRangeClient = \Mockery::mock(\UKFast\Admin\Networking\IpRangeClient::class);

        app()->bind(\UKFast\Admin\Networking\AdminClient::class, function () {
            return $this->mockNetworkAdminClient;
        });

        $this->mockNetworkAdminClient->shouldReceive('ipRanges')
            ->andReturn($this->mockAdminIpRangeClient)
            ->zeroOrMoreTimes();
    }

    public function testCapacityExpectedWithFloatingIPs()
    {
        Model::withoutEvents(function() {
            $this->floatingIp = factory(FloatingIp::class)->create([
                'id' => 'fip-test1',
                'vpc_id' => $this->vpc()->id,
                'ip_address' => '203.0.113.5',
            ]);
            $this->floatingIp = factory(FloatingIp::class)->create([
                'id' => 'fip-test2',
                'vpc_id' => $this->vpc()->id,
                'ip_address' => '203.0.113.6',
            ]);

            $this->availabilityZoneCapacity = factory(AvailabilityZoneCapacity::class)->create([
                'id' => 'azc-test',
                'availability_zone_id' => $this->availabilityZone()->id,
                'current' => null
            ]);
        });

        $this->mockAdminIpRangeClient->shouldReceive('getPage')->andReturnUsing(function () {
            $mockIpRange = \Mockery::mock(\UKFast\Admin\Networking\Entities\IpRange::class);
            $mockIpRange->shouldReceive('totalPages')->andReturn(1);
            $mockIpRange->shouldReceive('getItems')->andReturn(
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

            return $mockIpRange;
        });

        // /24 netmask for TEST-NET-3 range should give us 254 usable addresses.
        $totalIps = 254;
        $usedIps = 2;

        $expectedPercentUsed = round(($usedIps / $totalIps) * 100, 2); // 0.79

        Event::fake([JobFailed::class]);

        dispatch(new UpdateFloatingIpCapacity($this->availabilityZone()));

        Event::assertNotDispatched(JobFailed::class);

        $this->availabilityZoneCapacity->refresh();
        $this->assertEquals($expectedPercentUsed, $this->availabilityZoneCapacity->current);
    }

    public function testIPRangeWithInvalidCidrIgnored()
    {
        Model::withoutEvents(function() {
            $this->floatingIp = factory(FloatingIp::class)->create([
                'id' => 'fip-test1',
                'vpc_id' => $this->vpc()->id,
                'ip_address' => '203.0.113.5',
            ]);
            $this->floatingIp = factory(FloatingIp::class)->create([
                'id' => 'fip-test2',
                'vpc_id' => $this->vpc()->id,
                'ip_address' => '203.0.113.6',
            ]);
            $this->floatingIp = factory(FloatingIp::class)->create([
                'id' => 'fip-test3',
                'vpc_id' => $this->vpc()->id,
                'ip_address' => '203.0.110.6',
            ]);

            $this->availabilityZoneCapacity = factory(AvailabilityZoneCapacity::class)->create([
                'id' => 'azc-test',
                'availability_zone_id' => $this->availabilityZone()->id,
                'current' => null
            ]);
        });

        $this->mockAdminIpRangeClient->shouldReceive('getPage')->andReturnUsing(function () {
            $mockIpRange = \Mockery::mock(\UKFast\Admin\Networking\Entities\IpRange::class);
            $mockIpRange->shouldReceive('totalPages')->andReturn(1);
            $mockIpRange->shouldReceive('getItems')->andReturn(
                new Collection([
                    new \UKFast\Admin\Networking\Entities\IpRange(
                        [
                            "id" => 9028,
                            "description" => "203.0.110.0\/24 TEST-NET-2",
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
                            "networkAddress" => 3405803008,
                            "cidr" => 90,
                            "type" => "External",
                            "vrfNumber" => 0
                        ]
                    ),
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

            return $mockIpRange;
        });

        // /24 netmask for TEST-NET-3 range should give us 254 usable addresses.
        $totalIps = 254;
        $usedIps = 2;

        $expectedPercentUsed = round(($usedIps / $totalIps) * 100, 2); // 0.79

        Event::fake([JobFailed::class]);

        dispatch(new UpdateFloatingIpCapacity($this->availabilityZone()));

        Event::assertNotDispatched(JobFailed::class);

        $this->availabilityZoneCapacity->refresh();
        $this->assertEquals($expectedPercentUsed, $this->availabilityZoneCapacity->current);
    }

    public function testAvailabilityZoneWithNoAvailabilityZoneCapacitySkipped()
    {
        $az = Model::withoutEvents(function() {
            return factory(AvailabilityZone::class)->create([
                'id' => 'az-noazcapacity',
            ]);
        });

        Event::fake([JobFailed::class]);

        dispatch(new UpdateFloatingIpCapacity($az));

        Event::assertNotDispatched(JobFailed::class);
    }
}
