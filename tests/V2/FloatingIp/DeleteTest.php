<?php

namespace Tests\V2\FloatingIp;

use App\Events\V2\Task\Created;
use App\Models\V2\VpnEndpoint;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class DeleteTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testSuccess()
    {
        Event::fake(Created::class);

        $this->delete('/v2/floating-ips/' . $this->floatingIp()->id)
            ->assertResponseStatus(202);
    }

    public function testAssignedFloatingIpCanNotBeDeleted()
    {
        $vpnEndpoint = factory(VpnEndpoint::class)->create();

        $this->floatingIp()->resource()->associate($vpnEndpoint)->save();

        $this->delete('/v2/floating-ips/' . $this->floatingIp()->id, [
            'resource_id' => $this->nic()->id
        ])
            ->assertResponseStatus(403);
    }
}
