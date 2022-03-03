<?php

namespace Tests\Unit\Jobs\Nic;

use App\Jobs\Nsx\Nic\RemoveDHCPLease;
use App\Models\V2\IpAddress;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RemoveDHCPLeaseTest extends TestCase
{
    public function testIpInUseByNicOnSameNetworkIncremented()
    {
        $this->nsxServiceMock()->expects('delete')
            ->withSomeOfArgs(
                '/policy/api/v1/infra/tier-1s/' . $this->router()->id .
                '/segments/' . $this->network()->id .
                '/dhcp-static-binding-configs/' . $this->nic()->id
            )
            ->andReturnUsing(function () {
                return new Response(200);
            });

        $ipAddress = IpAddress::factory()->create([
            'ip_address' => '10.0.0.4',
        ]);

        $this->nic()->ipAddresses()->save($ipAddress);

        Event::fake([JobFailed::class]);

        $this->assertTrue($this->nic()->ipAddresses()->count() > 0);

        dispatch(new RemoveDHCPLease($this->nic()));

        $this->assertTrue($this->nic()->ipAddresses()->count() == 0);

        Event::assertNotDispatched(JobFailed::class);
    }
}
