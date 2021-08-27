<?php

namespace Tests\V2\VpnService;

use App\Events\V2\Task\Created;
use App\Models\V2\VpnService;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    protected $vpn;

    public function setUp(): void
    {
        parent::setUp();
        $this->vpn = factory(VpnService::class)->create([
            'name' => 'Unit Test VPN',
            'router_id' => $this->router()->id,
        ]);
    }

    public function testSuccessfulDelete()
    {
        Event::fake(Created::class);
        $this->delete(
            '/v2/vpn-services/' . $this->vpn->id,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertResponseStatus(202);
        Event::assertDispatched(Created::class);
    }
}
