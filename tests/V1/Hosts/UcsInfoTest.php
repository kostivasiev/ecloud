<?php

namespace Tests\Hosts;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;
use App\Models\V1\Pod;
use App\Models\V1\Host;
use App\Models\V1\Solution;

class UcsInfoTest extends TestCase
{
    use DatabaseMigrations;

    public function testHostWithCredentials()
    {
        // Pod, Solution and Host setup
        factory(Pod::class)->create([
            'ucs_datacentre_vce_server_id' => 1,
            'ucs_datacentre_ucs_api_url' => 'http://localhost'
        ])->first();
        factory(Solution::class)->create();
        $host = factory(Host::class)->create()->first();

        // Mock the Devices API
        $mockAdminClient = \Mockery::mock(\UKFast\Admin\Devices\AdminClient::class);
        $mockAdminDeviceClient = \Mockery::mock(\UKFast\Admin\Devices\AdminDeviceClient::class);
        $mockCredentials = \Mockery::mock(\UKFast\Admin\Devices\Entities\Credentials::class);
        $mockAdminCredentialsClient = \Mockery::mock(\UKFast\Admin\Devices\AdminCredentialsClient::class);
        app()->bind(\UKFast\Admin\Devices\AdminClient::class, function() use ($mockAdminClient) {
            return $mockAdminClient;
        });

        // Mock the Devices API "$adminClient->devices()->getCredentials(...)->getItems()[0]" call
        $mockAdminClient->shouldReceive('devices')
            ->andReturn($mockAdminDeviceClient);
        $mockAdminDeviceClient->shouldReceive('getCredentials')
            ->withArgs([1, 1, 1, ['type' => 'API', 'user' => 'conjurerapi']])
            ->andReturn($mockCredentials);
        $mockCredentials->shouldReceive('getItems')
            ->andReturn([
                0 => (object)[
                    'id' => 1,
                    'loginPort' => 80,
                    'password' => 'password'
                ]
            ]);

        // Mock the Devices API "$adminClient->credentials()->getPassword(...)" call
        $mockAdminClient->shouldReceive('credentials')
            ->andReturn($mockAdminCredentialsClient);
        $mockAdminCredentialsClient->shouldReceive('getPassword')
            ->withArgs([1])
            ->andReturn('password');

        // Mock the Conjurer API
        $client = \Mockery::mock(Client::class);
        app()->bind(Client::class, function() use ($client) {
            return $client;
        });

        // Mock the Conjurer API "$client->request(...)" call
        $client->shouldReceive('request')
            ->withArgs([
                'GET',
                '/api/v1/compute/' . urlencode($host->ucs_node_location) .
                    '/solution/' . (int)$host->ucs_node_ucs_reseller_id .
                    '/node/' . urlencode($host->ucs_node_profile_id),
                [
                    'auth' => ['conjurerapi', 'password'],
                    'headers' => [
                        'X-UKFast-Compute-Username' => 'conjurerapi',
                        'X-UKFast-Compute-Password' => 'password',
                        'Accept' => 'application/json',
                    ]
                ]
            ])
            ->andReturn(new Response(200, [], json_encode([
                'data' => ['key' => 'value'],
                'meta' => [],
            ])));

        // Finally UCS Info endpoint and check the expected result matches
        $this->get('/v1/hosts/' . $host->ucs_node_id . '/ucs_info', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ]);
        $this->assertResponseStatus(200);
        $this->seeJson([
            'data' => ['key' => 'value'],
            'meta' => [],
        ]);
    }

    public function testHostWithoutCredentials()
    {
        factory(Pod::class)->create();
        factory(Solution::class)->create();
        $host = factory(Host::class)->create()->first();

        $this->get('/v1/hosts/' . $host->ucs_node_id . '/ucs_info', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ]);

        $this->assertResponseStatus(200);
        $this->seeJson([
            'data' => [],
            'meta' => [],
        ]);
    }
}
