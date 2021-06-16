<?php
namespace Tests\V2\VpnSession;

use App\Models\V2\FloatingIp;
use App\Models\V2\VpnEndpoint;
use App\Models\V2\VpnService;
use App\Models\V2\VpnSession;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CreateTest extends TestCase
{
    protected VpnService $vpnService;
    protected VpnEndpoint $vpnEndpoint;
    protected VpnSession $vpnSession;
    protected FloatingIp $floatingIp;

    public function setUp(): void
    {
        parent::setUp();

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->floatingIp = FloatingIp::withoutEvents(function () {
            return factory(FloatingIp::class)->create([
                'id' => 'fip-abc123xyz',
                'vpc_id' => $this->vpc()->id,
            ]);
        });
        $this->vpnService = factory(VpnService::class)->create([
            'router_id' => $this->router()->id,
        ]);
        $this->vpnEndpoint = factory(VpnEndpoint::class)->create([
            'floating_ip_id' => $this->floatingIp->id,
        ]);
        $this->vpnSession = factory(VpnSession::class)->create(
            [
                'name' => '',
                'remote_ip' => '211.12.13.1',
                'remote_networks' => '127.1.1.1/32',
                'local_networks' => '127.1.1.1/32,127.1.10.1/24',
            ]
        );
    }

    public function testCreateResource()
    {
        $counter = 0;
        $services = factory(VpnService::class, 2)->create([
            'router_id' => $this->router()->id,
        ])->each(function ($service) use ($counter) {
            $counter++;
            $service->name = 'test-service-' . $counter;
            $service->save();
        });

        $this->post(
            '/v2/vpn-sessions',
            [
                'name' => 'vpn session test',
                'vpn_service_id' => [
                    $services[0]->id,
                    $services[1]->id,
                ],
                'vpn_endpoint_id' => [
                    $this->vpnEndpoint->id,
                ],
                'remote_ip' => '211.12.13.1',
                'remote_networks' => '172.12.23.11/32',
                'local_networks' => '172.11.11.11/32,176.18.22.11/24',
            ]
        )->assertResponseStatus(202);

        $vnpSessionId = (json_decode($this->response->getContent()))->data->id;
        $this->get('/v2/vpn-sessions/' . $vnpSessionId . '/services')
            ->seeJson(
                [
                    'id' => $services[0]->id,
                ]
            )->seeJson(
                [
                    'id' => $services[1]->id,
                ]
            )->assertResponseStatus(200);
    }

    public function testCreateResourceInvalidAndDuplicateService()
    {
        $this->post(
            '/v2/vpn-sessions',
            [
                'name' => 'vpn session test',
                'vpn_service_id' => [
                    'vnps-00000000',
                    'vnps-00000000',
                ],
                'vpn_endpoint_id' => [
                    $this->vpnEndpoint->id,
                ],
                'remote_ip' => '211.12.13.1',
                'remote_networks' => '172.12.23.11/32',
                'local_networks' => '172.11.11.11/32,176.18.22.11/24',
            ]
        )->seeJson(
            [
                'title' => 'Validation Error',
                'detail' => 'The selected vpn_service_id.0 is invalid',
            ]
        )->seeJson(
            [
                'title' => 'Validation Error',
                'detail' => 'The selected vpn_service_id.1 is invalid',
            ]
        )->seeJson(
            [
                'title' => 'Validation Error',
                'detail' => 'The vpn_service_id.0 field has a duplicate value',
            ]
        )->seeJson(
            [
                'title' => 'Validation Error',
                'detail' => 'The vpn_service_id.1 field has a duplicate value',
            ]
        )->assertResponseStatus(422);
    }

    public function testCreateResourceWithInvalidIps()
    {
        $service = factory(VpnService::class)->create([
            'name' => 'test-service',
            'router_id' => $this->router()->id,
        ]);

        $this->post(
            '/v2/vpn-sessions',
            [
                'name' => 'vpn session test',
                'vpn_service_id' => [
                    $service->id,
                ],
                'vpn_endpoint_id' => [
                    $this->vpnEndpoint->id,
                ],
                'remote_ip' => 'INVALID',
                'remote_networks' => 'INVALID',
                'local_networks' => 'INVALID',
            ]
        )->seeJson([
            'detail' => 'The remote ip must be a valid IPv4 address',
            'source' => 'remote_ip',
        ])->seeJson([
            'detail' => 'The remote networks must contain a valid comma separated list of CIDR subnets',
            'source' => 'remote_networks',
        ])->seeJson([
            'detail' => 'The local networks must contain a valid comma separated list of CIDR subnets',
            'source' => 'local_networks',
        ])->assertResponseStatus(422);
    }
}