<?php

namespace Tests\V2\Router;

use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class GetTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->availabilityZone();

        // TODO - Replace with real mock
        $this->nsxServiceMock()->allows('patch')
            ->andReturns(
                new Response(200, [], ''),
            );

        // TODO - Replace with real mock
        $this->nsxServiceMock()->allows('get')
            ->andReturns(
                new Response(200, [], json_encode(['publish_status' => 'REALIZED']))
            );
    }

    public function testGetCollection()
    {
        $this->router();

        $this->get(
            '/v2/routers',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->assertJsonFragment([
                'id' => $this->router()->id,
                'name' => $this->router()->name,
                'vpc_id' => $this->router()->vpc_id,
            ])
            ->assertStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->get(
            '/v2/routers/' . $this->router()->id,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->assertJsonFragment([
                'id' => $this->router()->id,
                'name' => $this->router()->name,
                'vpc_id' => $this->router()->vpc_id
            ])
            ->assertStatus(200);
    }

    public function testRouterFirewallPolicies()
    {
        $this->firewallPolicy();

        $this->get(
            '/v2/routers/' . $this->router()->id . '/firewall-policies',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->assertJsonFragment([
                'id' => $this->firewallPolicy()->id,
                'name' => $this->firewallPolicy()->name,
                'router_id' => $this->router()->id,
                'sequence' => $this->firewallPolicy()->sequence,
            ])
            ->assertStatus(200);
    }
}
