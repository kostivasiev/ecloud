<?php

namespace Tests\unit\Jobs\Nic;

use App\Jobs\Nsx\Nic\UnbindIpAddress;
use App\Models\V2\IpAddress;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UnbindIpAddressTest extends TestCase
{
    public function testBindIpAddress()
    {
        $this->nic()->ipAddresses()->save(IpAddress::factory()->create([
            'network_id' => $this->network()->id,
            'type' => IpAddress::TYPE_CLUSTER,
            'ip_address' => '1.1.1.1'
        ]));

        // To be unbound
        $ipAddress = IpAddress::factory()->create([
            'network_id' => $this->network()->id,
            'type' => IpAddress::TYPE_CLUSTER,
            'ip_address' => '2.2.2.2'
        ]);
        $this->nic()->ipAddresses()->save($ipAddress);

        $this->assertEquals(2, $this->nic()->ipAddresses()->count());

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

        Event::fake([JobFailed::class]);

        dispatch(new UnbindIpAddress($this->nic(), $ipAddress));

        Event::assertNotDispatched(JobFailed::class);

        $this->assertEquals(1, $this->nic()->ipAddresses()->count());
    }
}