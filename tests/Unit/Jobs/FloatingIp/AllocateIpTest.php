<?php

namespace Tests\Unit\Jobs\FloatingIp;

use App\Jobs\FloatingIp\AllocateIp;
use App\Models\V2\FloatingIp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Admin\SafeDNS\AdminClient;
use UKFast\Admin\SafeDNS\AdminRecordClient;
use UKFast\SDK\SafeDNS\Entities\Record;

class AllocateIpTest extends TestCase
{
    protected FloatingIp $floatingIp;
    protected $mockNetworkAdminClient;
    protected $mockAdminIpRangeClient;

    public function setUp(): void
    {
        parent::setUp();
        $mockRecordAdminClient = \Mockery::mock(AdminRecordClient::class);

        $mockSafednsAdminClient = \Mockery::mock(AdminClient::class);

        $mockSafednsAdminClient->shouldReceive('records')->andReturn(
            $mockRecordAdminClient
        );
        app()->bind(AdminClient::class, function () use ($mockSafednsAdminClient) {
            return $mockSafednsAdminClient;
        });

        app()->bind(AdminRecordClient::class, function () use ($mockRecordAdminClient) {
            return $mockRecordAdminClient;
        });

        $mockRecordAdminClient->shouldReceive('getPage')->andReturnUsing(function () {
            $mockRecord = \Mockery::mock(Record::class);
            $mockRecord->shouldReceive('totalPages')->andReturn(1);
            $mockRecord->shouldReceive('getItems')->andReturn(
                new Collection([
                    new \UKFast\SDK\SafeDNS\Entities\Record(
                        [
                            "id" => 10015521,
                            "zone" => "1.2.3.in-addr.arpa",
                            "name" => "1.2.3.4.in-addr.arpa",
                            "type" => "PTR",
                            "content" => "198.172.168.0.srvlist.co.uk",
                            "updated_at" => "1970-01-01T01:00:00+01:00",
                            "ttl" => 86400,
                            "priority" => null
                        ]
                    )
                ])
            );

            return $mockRecord;
        });

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
            $this->floatingIp = FloatingIp::factory()->create([
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
                            "description" => "203.0.113.0\/30 TEST-NET-3",
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
                            "networkAddress" => 3405803776,
                            "cidr" => 30,
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
        $this->assertContains($this->floatingIp->ip_address, ['203.0.113.0', '203.0.113.1','203.0.113.2','203.0.113.3']);
    }

    public function testNextAvailableIPAllocatedWithAvailableIPAddresses()
    {
        Model::withoutEvents(function() {
            FloatingIp::factory()->create([
                'id' => 'fip-existing1',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'ip_address' => '203.0.113.0',
            ]);
            FloatingIp::factory()->create([
                'id' => 'fip-existing2',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'ip_address' => '203.0.113.1',
            ]);
            FloatingIp::factory()->create([
                'id' => 'fip-existing3',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'ip_address' => '203.0.113.2',
            ]);
            $this->floatingIp = FloatingIp::factory()->create([
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
                            "description" => "203.0.113.0\/30 TEST-NET-3",
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
                            "networkAddress" => 3405803776,
                            "cidr" => 30,
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
        $this->assertEquals('203.0.113.3', $this->floatingIp->ip_address);
    }

    public function testNextAvailableIPAllocatedWithAvailableIPAddressesInSecondRange()
    {
        Model::withoutEvents(function() {
            FloatingIp::factory()->create([
                'id' => 'fip-existing1',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'ip_address' => '203.0.110.0',
            ]);
            FloatingIp::factory()->create([
                'id' => 'fip-existing2',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'ip_address' => '203.0.110.1',
            ]);
            FloatingIp::factory()->create([
                'id' => 'fip-existing3',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'ip_address' => '203.0.110.2',
            ]);
            FloatingIp::factory()->create([
                'id' => 'fip-existing4',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'ip_address' => '203.0.110.3',
            ]);
            $this->floatingIp = FloatingIp::factory()->create([
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
                            "description" => "203.0.113.0\/30 TEST-NET-3",
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
                            "networkAddress" => 3405803776,
                            "cidr" => 30,
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
        $this->assertContains($this->floatingIp->ip_address, ['203.0.113.0','203.0.113.1','203.0.113.2','203.0.113.3']);
    }

    public function testInvalidIPRangeSubnetIsSkipped()
    {
        Model::withoutEvents(function() {
            $this->floatingIp = FloatingIp::factory()->create([
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
                            "description" => "203.0.113.0\/30 TEST-NET-3",
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
                            "networkAddress" => 3405803776,
                            "cidr" => 30,
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
        $this->assertContains($this->floatingIp->ip_address, ['203.0.113.0','203.0.113.1','203.0.113.2','203.0.113.3']);
    }

    public function testNoAvailableIPAddressesFails()
    {
        Model::withoutEvents(function() {
            FloatingIp::factory()->create([
                'id' => 'fip-existing1',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'ip_address' => '203.0.110.0',
            ]);
            FloatingIp::factory()->create([
                'id' => 'fip-existing2',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'ip_address' => '203.0.110.1',
            ]);
            FloatingIp::factory()->create([
                'id' => 'fip-existing3',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'ip_address' => '203.0.110.2',
            ]);
            FloatingIp::factory()->create([
                'id' => 'fip-existing4',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'ip_address' => '203.0.110.3',
            ]);
            $this->floatingIp = FloatingIp::factory()->create([
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
