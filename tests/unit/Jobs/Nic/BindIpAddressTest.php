<?php

namespace Tests\unit\Jobs\Nic;

use App\Jobs\Nsx\Nic\BindIpAddress;
use App\Models\V2\IpAddress;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class BindIpAddressTest extends TestCase
{
    public function testBindIpAddress()
    {
        $ipAddress = IpAddress::factory()->create([
            'network_id' => $this->network()->id,
            'type' => 'cluster'
        ]);

        $this->nsxServiceMock()->expects('put')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/' . $this->router()->id .
                '/segments/' . $this->network()->id .
                '/ports/' . $this->nic()->id,
                [
                    'json' => [
                        'resource_type' => 'SegmentPort',
                        "address_bindings" => [
                            [
                                'ip_address' => $ipAddress->ip_address,
                                'mac_address' => 'AA:BB:CC:DD:EE:FF'
                            ]
                        ]
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });


        Event::fake([JobFailed::class]);

        dispatch(new BindIpAddress($this->nic(), $ipAddress));

        Event::assertNotDispatched(JobFailed::class);

        $this->assertEquals(1, $this->nic()->ipAddresses()->count());
    }

    public function testBindMultipleIpAddresses()
    {
        $ipAddress = IpAddress::factory()->create([
            'network_id' => $this->network()->id,
            'type' => 'cluster'
        ]);

        $this->nsxServiceMock()->expects('patch')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/' . $this->router()->id .
                '/segments/' . $this->network()->id .
                '/ports/' . $this->nic()->id,
                [
                    'json' => [
                        'resource_type' => 'SegmentPort',
                        "address_bindings" => [
                            [
                                'ip_address' => '2.2.2.2',
                                'mac_address' => 'AA:BB:CC:DD:EE:FF'
                            ],
                            [
                                'ip_address' => '1.1.1.1',
                                'mac_address' => 'AA:BB:CC:DD:EE:FF'
                            ]
                        ]
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        $this->nic()->ipAddresses()->save(IpAddress::factory()->create([
            'network_id' => $this->network()->id,
            'type' => 'cluster',
            'ip_address' => '2.2.2.2'
        ]));

        Event::fake([JobFailed::class]);

        dispatch(new BindIpAddress($this->nic(), $ipAddress));

        Event::assertNotDispatched(JobFailed::class);

        $this->nic()->refresh();

        $this->assertEquals(2, $this->nic()->ipAddresses()->count());
    }

    public function testOnlyClusterIpsAreBound()
    {
        $ipAddress = IpAddress::factory()->create([
            'network_id' => $this->network()->id,
            'type' => 'cluster'
        ]);

        $this->nsxServiceMock()->expects('patch')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/' . $this->router()->id .
                '/segments/' . $this->network()->id .
                '/ports/' . $this->nic()->id,
                [
                    'json' => [
                        'resource_type' => 'SegmentPort',
                        "address_bindings" => [
                            [
                                'ip_address' => $ipAddress->ip_address,
                                'mac_address' => 'AA:BB:CC:DD:EE:FF'
                            ]
                        ]
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        Event::fake([JobFailed::class]);

        // Mock the DHCP IP address
        $this->nic()->ipAddresses()->save(IpAddress::factory()->create([
            'network_id' => $this->network()->id,
            'type' => IpAddress::TYPE_NORMAL,
            'ip_address' => '2.2.2.2'
        ]));

        dispatch(new BindIpAddress($this->nic(), $ipAddress));

        Event::assertNotDispatched(JobFailed::class);

        $this->assertEquals(2, $this->nic()->ipAddresses()->count());
    }
}