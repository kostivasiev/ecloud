<?php

namespace Tests\unit\Jobs\FloatingIp;

use App\Jobs\FloatingIp\AllocateIp;
use App\Models\V2\FloatingIp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AllocateIpTest extends TestCase
{
    protected FloatingIp $floatingIp;
    protected $mockNetworkAdminClient;
    protected $mockAdminIpRangeClient;

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

    public function testIPAllocatedWithAvailableIPAddresses()
    {
        Model::withoutEvents(function() {
            $this->floatingIp = factory(FloatingIp::class)->create([
                'id' => 'fip-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'ip_address' => '',
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

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AllocateIp($this->floatingIp));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        $this->floatingIp->refresh();
        $this->assertEquals('203.0.113.0', $this->floatingIp->ip_address);
    }

    public function testNextAvailableIPAllocatedWithAvailableIPAddresses()
    {
        Model::withoutEvents(function() {
            factory(FloatingIp::class)->create([
                'id' => 'fip-existing1',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'ip_address' => '203.0.113.0',
            ]);
            factory(FloatingIp::class)->create([
                'id' => 'fip-existing2',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'ip_address' => '203.0.113.1',
            ]);
            $this->floatingIp = factory(FloatingIp::class)->create([
                'id' => 'fip-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'ip_address' => '',
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

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AllocateIp($this->floatingIp));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        $this->floatingIp->refresh();
        $this->assertEquals('203.0.113.2', $this->floatingIp->ip_address);
    }

    public function testNextAvailableIPAllocatedWithAvailableIPAddressesInSecondRange()
    {
        Model::withoutEvents(function() {
            factory(FloatingIp::class)->create([
                'id' => 'fip-existing1',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'ip_address' => '203.0.110.0',
            ]);
            factory(FloatingIp::class)->create([
                'id' => 'fip-existing2',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'ip_address' => '203.0.110.1',
            ]);
            factory(FloatingIp::class)->create([
                'id' => 'fip-existing3',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'ip_address' => '203.0.110.2',
            ]);
            factory(FloatingIp::class)->create([
                'id' => 'fip-existing4',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'ip_address' => '203.0.110.3',
            ]);
            $this->floatingIp = factory(FloatingIp::class)->create([
                'id' => 'fip-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'ip_address' => '',
            ]);
        });

        $this->mockAdminIpRangeClient->shouldReceive('getPage')->andReturnUsing(function () {
            $mockIpRange = \Mockery::mock(\UKFast\Admin\Networking\Entities\IpRange::class);
            $mockIpRange->shouldReceive('totalPages')->andReturn(1);
            $mockIpRange->shouldReceive('getItems')->andReturn(
                new Collection([
                    new \UKFast\Admin\Networking\Entities\IpRange(
                        [
                            "id" => 9027,
                            "description" => "203.0.110.0\/30 TEST-NET-2",
                            "externalSubnet" => "255.255.255.252",
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
                            "cidr" => 30,
                            "type" => "External",
                            "vrfNumber" => 0
                        ],
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
                        ],
                    )
                ])
            );

            return $mockIpRange;
        });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AllocateIp($this->floatingIp));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        $this->floatingIp->refresh();
        $this->assertEquals('203.0.113.0', $this->floatingIp->ip_address);
    }

    public function testInvalidIPRangeSubnetIsSkipped()
    {
        Model::withoutEvents(function() {
            $this->floatingIp = factory(FloatingIp::class)->create([
                'id' => 'fip-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'ip_address' => '',
            ]);
        });

        $this->mockAdminIpRangeClient->shouldReceive('getPage')->andReturnUsing(function () {
            $mockIpRange = \Mockery::mock(\UKFast\Admin\Networking\Entities\IpRange::class);
            $mockIpRange->shouldReceive('totalPages')->andReturn(1);
            $mockIpRange->shouldReceive('getItems')->andReturn(
                new Collection([
                    new \UKFast\Admin\Networking\Entities\IpRange(
                        [
                            "id" => 9027,
                            "description" => "203.0.110.0\/30 TEST-NET-2",
                            "externalSubnet" => "255.255.255.252",
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
                        ],
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
                        ],
                    )
                ])
            );

            return $mockIpRange;
        });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AllocateIp($this->floatingIp));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        $this->floatingIp->refresh();
        $this->assertEquals('203.0.113.0', $this->floatingIp->ip_address);
    }

    public function testNoAvailableIPAddressesFails()
    {
        Model::withoutEvents(function() {
            factory(FloatingIp::class)->create([
                'id' => 'fip-existing1',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'ip_address' => '203.0.110.0',
            ]);
            factory(FloatingIp::class)->create([
                'id' => 'fip-existing2',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'ip_address' => '203.0.110.1',
            ]);
            factory(FloatingIp::class)->create([
                'id' => 'fip-existing3',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'ip_address' => '203.0.110.2',
            ]);
            factory(FloatingIp::class)->create([
                'id' => 'fip-existing4',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'ip_address' => '203.0.110.3',
            ]);
            $this->floatingIp = factory(FloatingIp::class)->create([
                'id' => 'fip-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'ip_address' => '',
            ]);
        });

        $this->mockAdminIpRangeClient->shouldReceive('getPage')->andReturnUsing(function () {
            $mockIpRange = \Mockery::mock(\UKFast\Admin\Networking\Entities\IpRange::class);
            $mockIpRange->shouldReceive('totalPages')->andReturn(1);
            $mockIpRange->shouldReceive('getItems')->andReturn(
                new Collection([
                    new \UKFast\Admin\Networking\Entities\IpRange(
                        [
                            "id" => 9027,
                            "description" => "203.0.110.0\/30 TEST-NET-2",
                            "externalSubnet" => "255.255.255.252",
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
                            "cidr" => 30,
                            "type" => "External",
                            "vrfNumber" => 0
                        ],
                    ),
                ])
            );

            return $mockIpRange;
        });

        Event::fake([JobFailed::class]);

        dispatch(new AllocateIp($this->floatingIp));

        Event::assertDispatched(JobFailed::class, function ($event) {
            return $event->exception->getMessage() == 'Insufficient available external IPs to assign to floating IP resource fip-test';
        });
    }
}
