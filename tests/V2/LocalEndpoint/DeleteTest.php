<?php
namespace Tests\V2\LocalEndpoint;

use App\Models\V2\FloatingIp;
use App\Models\V2\LocalEndpoint;
use App\Models\V2\Vpn;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class DeleteTest extends TestCase
{
    protected LocalEndpoint $localEndpoint;
    protected Vpn $vpn;

    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $floatingIp = FloatingIp::withoutEvents(function () {
            return factory(FloatingIp::class)->create([
                'id' => 'fip-abc123xyz',
                'vpc_id' => $this->vpc()->id,
                'ip_address' => '203.0.113.1',
            ]);
        });
        $this->vpn = factory(Vpn::class)->create([
            'router_id' => $this->router()->id,
        ]);
        $this->localEndpoint = factory(LocalEndpoint::class)->create(
            [
                'name' => 'Get Test',
                'vpn_id' => $this->vpn->id,
                'fip_id' => $floatingIp->id,
            ]
        );
    }

    public function testDeleteResource()
    {
        $this->delete('/v2/local-endpoints/' . $this->localEndpoint->id)
            ->assertResponseStatus(204);
    }

    public function testDeleteResourceWrongUser()
    {
        $this->be(new Consumer(999, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->delete('/v2/local-endpoints/' . $this->localEndpoint->id)
            ->seeJson(
                [
                    'title' => 'Not found',
                    'detail' => 'No Local Endpoint with that ID was found',
                ]
            )->assertResponseStatus(404);
    }
}