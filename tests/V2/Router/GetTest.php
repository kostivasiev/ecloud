<?php

namespace Tests\V2\Router;

use GuzzleHttp\Psr7\Response;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class GetTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->availabilityZone();

        // TODO - Replace with real mock
        $this->nsxServiceMock()->shouldReceive('patch')
            ->andReturn(
                new Response(200, [], ''),
            );

        // TODO - Replace with real mock
        $this->nsxServiceMock()->shouldReceive('get')
            ->andReturn(
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
            ->seeJson([
                'id' => $this->router()->id,
                'name' => $this->router()->name,
                'vpc_id' => $this->router()->vpc_id,
            ])
            ->assertResponseStatus(200);
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
            ->seeJson([
                'id' => $this->router()->id,
                'name' => $this->router()->name,
                'vpc_id' => $this->router()->vpc_id
            ])
            ->assertResponseStatus(200);
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
            ->seeJson([
                'id' => $this->firewallPolicy()->id,
                'name' => $this->firewallPolicy()->name,
                'router_id' => $this->router()->id,
                'sequence' => $this->firewallPolicy()->sequence,
            ])
            ->assertResponseStatus(200);
    }


    public function testGetManagedRouterNotAdminFails()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

       exit(print_r(
          $this->router()
       ));

        $this->router()->setAttribute('is_managed', true)->save();

//        $this->get(
//            '/v2/routers/' . $this->router()->id,
//        )
//            ->assertResponseStatus(404);
    }
}
