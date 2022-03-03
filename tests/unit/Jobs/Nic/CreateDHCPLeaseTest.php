<?php

namespace Tests\unit\Jobs\Nic;

use App\Jobs\Nsx\Nic\CreateDHCPLease;
use App\Models\V2\IpAddress;
use App\Models\V2\Network;
use App\Models\V2\Nic;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use IPLib\Range\Subnet;
use Tests\TestCase;

class CreateDHCPLeaseTest extends TestCase
{
    public function testAlreadyAssignedDhcpIpAddressSkips()
    {
        Event::fake([JobFailed::class]);

        $ipAddress = IpAddress::factory()->create();

        $this->nic()->ipAddresses()->save($ipAddress);

        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs(
                '/policy/api/v1/infra/tier-1s/' . $this->router()->id .
                '/segments/' . $this->network()->id .
                '/dhcp-static-binding-configs?cursor='
            )
            ->andReturnUsing(function () use ($ipAddress) {
                return new Response(200, [], json_encode([
                    'results' => [
                        [
                            'id' => $this->nic()->id,
                            'ip_address' => $ipAddress->ip_address
                        ]
                    ]
                ]));
            });

        dispatch(new CreateDHCPLease($this->nic()));

        $this->nsxServiceMock()->shouldNotHaveReceived('put');

        $this->assertEquals(1, $this->nic()->ipAddresses()->count());

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testIpInUseByNicOnSameNetworkIncremented()
    {
        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/policy/api/v1/infra/tier-1s/' . $this->router()->id . '/segments/' . $this->network()->id . '/dhcp-static-binding-configs?cursor=')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => []
                ]));
            });

        $this->nsxServiceMock()->expects('put')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/' . $this->router()->id . '/segments/' . $this->network()->id . '/dhcp-static-binding-configs/' . $this->nic()->id,
                [
                    'json' => [
                        "resource_type" => "DhcpV4StaticBindingConfig",
                        "mac_address" => "AA:BB:CC:DD:EE:FF",
                        "ip_address" => "10.0.0.5", // next IP .5 assigned
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        $nic = Nic::factory()->create([
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'instance_id' => $this->instanceModel()->id,
            'network_id' => $this->network()->id,
        ]);

        $ipAddress = IpAddress::factory()->create([
            'ip_address' => '10.0.0.4',
        ]);
        $ipAddress->network()->associate($this->network());

        $nic->ipAddresses()->save($ipAddress);

        Event::fake([JobFailed::class]);

        dispatch(new CreateDHCPLease($this->nic()));

        $this->assertEquals('10.0.0.5', $this->nic()->ip_address);

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testIpInUseByNicOnDifferentNetworkIgnored()
    {
        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/policy/api/v1/infra/tier-1s/' . $this->router()->id . '/segments/' . $this->network()->id . '/dhcp-static-binding-configs?cursor=')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => []
                ]));
            });

        $this->nsxServiceMock()->expects('put')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/' . $this->router()->id . '/segments/' . $this->network()->id . '/dhcp-static-binding-configs/' . $this->nic()->id,
                [
                    'json' => [
                        "resource_type" => "DhcpV4StaticBindingConfig",
                        "mac_address" => "AA:BB:CC:DD:EE:FF",
                        "ip_address" => "10.0.0.4", // next IP .4 assigned
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        // Create a NIC on a different network
        $network = Network::factory()->create([
            'id' => 'net-2',
            'subnet' => '10.0.0.0/24',
            'router_id' => $this->router()->id
        ]);

        $nic = Nic::factory()->create([
            'mac_address' => 'AA:AA:CC:DD:EE:FF',
            'instance_id' => $this->instanceModel()->id,
            'network_id' => $network->id,
        ]);

        $ipAddress = IpAddress::factory()->create([
            'ip_address' => '10.0.0.4',
        ]);
        $ipAddress->network()->associate($network);

        $nic->ipAddresses()->save($ipAddress);

        Event::fake([JobFailed::class]);

        dispatch(new CreateDHCPLease($this->nic()));

        $this->assertEquals('10.0.0.4', $this->nic()->ip_address);

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testNoIpAddressAvailableFails()
    {
        $network = Network::factory()->create([
            'id' => 'net-2',
            'subnet' => '172.17.2.0/29',
            'router_id' => $this->router()->id
        ]);

        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/policy/api/v1/infra/tier-1s/' . $this->router()->id . '/segments/' . $network->id . '/dhcp-static-binding-configs?cursor=')
            ->andReturnUsing(function () use ($network) {
                $subnet = Subnet::fromString($network->subnet);
                $ip = $subnet->getStartAddress();
                $used = [];
                while ($ip = $ip->getNextAddress()) {
                    if ($ip->toString() === $subnet->getEndAddress()->toString()) {
                        break;
                    }
                    $used[]['ip_address'] = $ip->toString();
                }

                return new Response(200, [], json_encode([
                    'results' => $used
                ]));
            });

        $nic = Nic::factory()->create([
            'mac_address' => 'AA:AA:CC:DD:EE:FF',
            'instance_id' => $this->instanceModel()->id,
            'network_id' => $network->id,
        ]);

        $this->expectExceptionMessage("Insufficient available IP's in subnet on network $network->id");

        Event::fake([JobFailed::class]);

        dispatch(new CreateDHCPLease($nic));

        Event::assertDispatched(JobFailed::class);
    }
}
