<?php

namespace Tests\unit\Jobs\Nic;

use App\Jobs\Nsx\Nic\RemoveIpAddressBindings;
use App\Models\V2\IpAddress;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RemoveIpAddressBindingsTest extends TestCase
{
    public function testSuccess()
    {
        $this->nic()->ipAddresses()->save(IpAddress::factory()->create([
            'network_id' => $this->network()->id,
            'type' => IpAddress::TYPE_CLUSTER,
            'ip_address' => '2.2.2.2'
        ]));

        $this->assertEquals(1, $this->nic()->ipAddresses()->count());

        $this->nsxServiceMock()->expects('delete')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/' . $this->router()->id .
                '/segments/' . $this->network()->id .
                '/ports/' . $this->nic()->id
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        Event::fake([JobFailed::class]);

        dispatch(new RemoveIpAddressBindings($this->nic()));

        Event::assertNotDispatched(JobFailed::class);

        $this->assertEquals(0, $this->nic()->ipAddresses()->count());
    }
}