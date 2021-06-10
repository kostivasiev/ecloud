<?php
namespace Tests\V2\LocalEndpoint;

use App\Models\V2\FloatingIp;
use App\Models\V2\LocalEndpoint;
use App\Models\V2\Vpn;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CreateTest extends TestCase
{
    protected FloatingIp $floatingIp;
    protected Vpn $vpn;

    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->floatingIp = FloatingIp::withoutEvents(function () {
            return factory(FloatingIp::class)->create([
                'id' => 'fip-abc123xyz',
                'vpc_id' => $this->vpc()->id,
                'ip_address' => '203.0.113.1',
            ]);
        });
        $this->vpn = factory(Vpn::class)->create([
            'router_id' => $this->router()->id,
        ]);
    }

    public function testCreateResource()
    {
        $data = [
            'name' => 'Create Test',
            'vpn_id' => $this->vpn->id,
            'fip_id' => $this->floatingIp->id,
        ];
        $this->post('/v2/local-endpoints', $data)
            ->assertResponseStatus(202);
    }

    public function testCreateResourceVpnAlreadyUsed()
    {
        $floatingIp = FloatingIp::withoutEvents(function () {
            return factory(FloatingIp::class)->create([
                'id' => 'fip-aaa111bbb',
                'vpc_id' => $this->vpc()->id,
                'ip_address' => '203.0.113.2',
            ]);
        });
        factory(LocalEndpoint::class)->create([
            'name' => 'Original Endpoint',
            'vpn_id' => $this->vpn->id,
            'fip_id' => $floatingIp->id,
        ]);
        $data = [
            'name' => 'Create Test',
            'vpn_id' => $this->vpn->id,
            'fip_id' => $this->floatingIp->id,
        ];
        $this->post('/v2/local-endpoints', $data)
            ->seeJson(
                [
                    'title' => 'Validation Error',
                    'detail' => 'A local endpoint already exists for the specified vpn id',
                ]
            )
            ->assertResponseStatus(422);
    }

    public function testCreateResourceFipInUse()
    {
        $vpn = factory(Vpn::class)->create([
            'router_id' => $this->router()->id,
        ]);
        factory(LocalEndpoint::class)->create([
            'name' => 'Original Endpoint',
            'vpn_id' => $vpn->id,
            'fip_id' => $this->floatingIp->id,
        ]);
        $data = [
            'name' => 'Create Test',
            'vpn_id' => $this->vpn->id,
            'fip_id' => $this->floatingIp->id,
        ];
        $this->post('/v2/local-endpoints', $data)
            ->seeJson(
                [
                    'title' => 'Validation Error',
                    'detail' => 'A local endpoint already exists for the specified fip id',
                ]
            )
            ->assertResponseStatus(422);
    }

    public function testCreateWithoutFloatingIp()
    {
        $floatingIp = FloatingIp::withoutEvents(function () {
            return factory(FloatingIp::class)->create([
                'id' => 'fip-aaa111bbb',
                'vpc_id' => $this->vpc()->id,
                'ip_address' => '203.0.113.2',
            ]);
        });
        app()->bind(FloatingIp::class, function () use ($floatingIp) {
            return $floatingIp;
        });

        $data = [
            'name' => 'Create Test',
            'vpn_id' => $this->vpn->id,
        ];
        $this->post('/v2/local-endpoints', $data)
            ->assertResponseStatus(202);

        $id = json_decode($this->response->getContent())->data->id;
        $localEndpoint = LocalEndpoint::findOrFail($id);
        $this->assertEquals($floatingIp->id, $localEndpoint->fip_id);
    }
}