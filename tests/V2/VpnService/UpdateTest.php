<?php

namespace Tests\V2\VpnService;

use App\Events\V2\Task\Created;
use App\Models\V2\VpnService;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    protected $vpnService;

    public function setUp(): void
    {
        parent::setUp();

        $this->vpnService = factory(VpnService::class)->create([
            'name' => 'Unit Test VPN',
            'router_id' => $this->router()->id,
        ]);
    }

    public function testValidDataIsSuccessful()
    {
        Event::fake(Created::class);
        $data = [
            'name' => 'Unit Test VPN (Updated)',
            'router_id' => $this->router()->id,
        ];
        $this->patch(
            '/v2/vpn-services/' . $this->vpnService->id,
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertResponseStatus(202);

        $vpnItem = VpnService::findOrFail($this->vpnService->id);
        $this->assertEquals($data['router_id'], $vpnItem->router_id);
        $this->assertEquals($data['name'], $vpnItem->name);
    }
}
