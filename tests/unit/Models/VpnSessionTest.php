<?php

namespace Tests\unit\Models;

use App\Models\V2\Task;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Tests\Mocks\Resources\VpnSessionMock;
use Tests\TestCase;

class VpnSessionTest extends TestCase
{
    use VpnSessionMock;

    public function testGetTunnelDetailsAttributeNullWithEmptyResults()
    {
        $this->nsxServiceMock()->expects('get')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/rtr-test/locale-services/rtr-test/ipsec-vpn-services/vpn-test/sessions/vpns-test/statistics',
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
            });

        $details = $this->vpnSession()->tunnel_details;

        $this->assertNull($details);
    }

    public function testGetTunnelDetailsAttributeExpectedSessionState()
    {
        $this->nsxServiceMock()->expects('get')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/rtr-test/locale-services/rtr-test/ipsec-vpn-services/vpn-test/sessions/vpns-test/statistics',
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [
                        [
                            'ike_status' => [
                                'ike_session_state' => 'UP'
                            ]
                        ]
                    ]
                ]));
            });

        $details = $this->vpnSession()->tunnel_details;

        $this->assertEquals('UP', $details->session_state);
        $this->assertCount(0, $details->tunnel_statistics);
    }

    public function testGetTunnelDetailsAttributeExpectedSessionStateasd()
    {
        $this->nsxServiceMock()->expects('get')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/rtr-test/locale-services/rtr-test/ipsec-vpn-services/vpn-test/sessions/vpns-test/statistics',
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [
                        [
                            'policy_statistics' => [
                                [
                                    'tunnel_statistics' => [
                                        [
                                            'tunnel_status' => 'DOWN',
                                            'tunnel_down_reason' => 'test reason',
                                            'local_subnet' => '10.0.0.0/24',
                                            'peer_subnet' => '192.168.0.0/24'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]));
            });

        $details = $this->vpnSession()->tunnel_details;

        $this->assertCount(1, $details->tunnel_statistics);
        $this->assertEquals('DOWN', $details->tunnel_statistics[0]->tunnel_status);
        $this->assertEquals('test reason', $details->tunnel_statistics[0]->tunnel_down_reason);
        $this->assertEquals('10.0.0.0/24', $details->tunnel_statistics[0]->local_subnet);
        $this->assertEquals('192.168.0.0/24', $details->tunnel_statistics[0]->peer_subnet);
    }
}
