<?php

namespace Tests\V1\Hosts;

use App\Models\V1\Host;
use App\Models\V1\Pod;
use App\Models\V1\Solution;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\DatabaseMigrations;;
use Tests\V1\TestCase;

class HardwareTest extends TestCase
{
    public function hostWithCredentialsDataProvider()
    {
        return [
            'get_credentials_with_login_port' => [
                'get_items_response' => [
                    0 => (object)[
                        'id' => 1,
                        'loginPort' => 80,
                        'password' => 'password'
                    ]
                ],
            ],
            'get_credentials_without_login_port' => [
                'get_items_response' => [
                    0 => (object)[
                        'id' => 1,
                        'password' => 'password'
                    ]
                ],
            ],
        ];
    }

    /**
     * @dataProvider hostWithCredentialsDataProvider
     * @param array $getItemsResponse
     */
    public function testHostWithCredentials($getItemsResponse)
    {
        // Pod, Pod location, Solution and Host setup
        Pod::factory()->create([
            'ucs_datacentre_vce_server_id' => 1,
            'ucs_datacentre_ucs_api_url' => 'http://localhost'
        ]);
        Pod\Location::factory()->create();
        Solution::factory()->create();
        $host = Host::factory()->create()->first();

        // Mock the Devices API
        $mockAdminClient = \Mockery::mock(\UKFast\Admin\Devices\AdminClient::class);
        $mockAdminDeviceClient = \Mockery::mock(\UKFast\Admin\Devices\AdminDeviceClient::class);
        $mockCredentials = \Mockery::mock(\UKFast\Admin\Devices\Entities\Credentials::class);
        $mockAdminCredentialsClient = \Mockery::mock(\UKFast\Admin\Devices\AdminCredentialsClient::class);
        app()->instance(\UKFast\Admin\Devices\AdminClient::class, $mockAdminClient);

        // Mock the Devices API "$adminClient->devices()->getCredentials(...)->getItems()[0]" call
        $mockAdminClient->shouldReceive('devices')
            ->andReturn($mockAdminDeviceClient);
        $mockAdminDeviceClient->shouldReceive('getCredentials')
            ->withArgs([1, 1, 1, ['type' => 'API', 'user' => 'conjurerapi']])
            ->andReturn($mockCredentials);
        $mockCredentials->shouldReceive('getItems')
            ->andReturn($getItemsResponse);

        // Mock the Devices API "$adminClient->credentials()->getPassword(...)" call
        $mockAdminClient->shouldReceive('credentials')
            ->andReturn($mockAdminCredentialsClient);
        $mockAdminCredentialsClient->shouldReceive('getPassword')
            ->withArgs([1])
            ->andReturn('password');

        // Mock the Devices API "$adminClient->devices()->getCredentials(...)->getItems()[0]" call
        $mockAdminClient->shouldReceive('devices')
            ->andReturn($mockAdminDeviceClient);
        $mockAdminDeviceClient->shouldReceive('getCredentials')
            ->withArgs([1, 1, 1, ['type' => 'API', 'user' => 'ucs-api']])
            ->andReturn($mockCredentials);
        $mockCredentials->shouldReceive('getItems')
            ->andReturn($getItemsResponse);

        // Mock the Devices API "$adminClient->credentials()->getPassword(...)" call
        $mockAdminClient->shouldReceive('credentials')
            ->andReturn($mockAdminCredentialsClient);
        $mockAdminCredentialsClient->shouldReceive('getPassword')
            ->withArgs([1])
            ->andReturn('password');

        // Mock the Conjurer API
        $client = \Mockery::mock(Client::class);
        app()->bind(Client::class, function ($app, $args) use ($client, $getItemsResponse) {
            $expectedUri = empty($getItemsResponse[0]->loginPort) ?
                'http://localhost' :
                'http://localhost:' . $getItemsResponse[0]->loginPort;
            $this->assertEquals($expectedUri, $args['config']['base_uri']);
            return $client;
        });

        // Mock the Conjurer API "$client->request(...)" call
        $client->shouldReceive('request')
            ->withArgs([
                'GET',
                '/api/v1/compute/' . urlencode($host->location->ucs_datacentre_location_name) .
                '/solution/' . (int)$host->ucs_node_ucs_reseller_id .
                '/node/' . urlencode($host->ucs_node_profile_id),
                [
                    'auth' => ['conjurerapi', 'password'],
                    'headers' => [
                        'X-UKFast-Compute-Username' => 'ucs-api',
                        'X-UKFast-Compute-Password' => 'password',
                        'Accept' => 'application/json',
                    ]
                ]
            ])
            ->andReturn(new Response(200, [], json_encode([
                'assigned' => 'assigned',
                'associated' => 'associated',
                'configurationState' => 'configurationState',
                'interfaces' => [
                    [
                        'name' => 'name',
                        'address' => 'address',
                        'type' => 'type',
                    ],
                ],
                'location' => 'location',
                'name' => 'name',
                'powerState' => 'powerState',
                'specification' => 'specification',
                "hardwareVersion" => "M4",
            ])));

        // Finally hit the UCS Info endpoint and check the expected result matches
        $this->get('/v1/hosts/' . $host->ucs_node_id . '/hardware', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertStatus(200)
            ->assertJsonFragment([
            'data' => [
                'assign_state' => 'assigned',
                'associate_state' => 'associated',
                'configuration_state' => 'configurationState',
                'interfaces' => [
                    [
                        'name' => 'name',
                        'address' => 'address',
                        'type' => 'type',
                    ],
                ],
                'location' => 'location',
                'name' => 'name',
                'power_state' => 'powerState',
                'specification' => 'specification',
                "hardwareVersion" => "M4",
            ],
            'meta' => [],
        ]);
    }

    public function testHostWithoutConjurerApiCredentials()
    {
        // Pod, Solution and Host setup
        Pod::factory()->create([
            'ucs_datacentre_vce_server_id' => 1,
            'ucs_datacentre_ucs_api_url' => 'http://localhost'
        ]);
        Solution::factory()->create();
        $host = Host::factory()->create()->first();

        // Mock the Devices API
        $mockAdminClient = \Mockery::mock(\UKFast\Admin\Devices\AdminClient::class);
        $mockAdminDeviceClient = \Mockery::mock(\UKFast\Admin\Devices\AdminDeviceClient::class);
        $mockCredentials = \Mockery::mock(\UKFast\Admin\Devices\Entities\Credentials::class);
        app()->bind(\UKFast\Admin\Devices\AdminClient::class, function () use ($mockAdminClient) {
            return $mockAdminClient;
        });

        // Mock the Devices API "$adminClient->devices()->getCredentials(...)->getItems()[0]" call
        $mockAdminClient->shouldReceive('devices')
            ->andReturn($mockAdminDeviceClient);
        $mockAdminDeviceClient->shouldReceive('getCredentials')
            ->withArgs([1, 1, 1, ['type' => 'API', 'user' => 'conjurerapi']])
            ->andReturn($mockCredentials);
        $mockCredentials->shouldReceive('getItems')
            ->andReturn([]);

        // Devices API "$adminClient->credentials()" call should never happen
        $mockAdminClient->shouldNotReceive('credentials');

        // Finally hit the UCS Info endpoint and check the expected result matches
        $this->get('/v1/hosts/' . $host->ucs_node_id . '/hardware', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertStatus(200)
            ->assertJsonFragment([
                'data' => [],
                'meta' => [],
            ]);
    }

    public function testHostWithoutUcsApiCredentials()
    {
        $getItemsResponse = [
            0 => (object)[
                'id' => 1,
                'password' => 'password'
            ]
        ];

        // Pod, Solution and Host setup
        Pod::factory()->create([
            'ucs_datacentre_vce_server_id' => 1,
            'ucs_datacentre_ucs_api_url' => 'http://localhost'
        ]);
        Solution::factory()->create();
        $host = Host::factory()->create()->first();

        // Mock the Devices API
        $mockAdminClient = \Mockery::mock(\UKFast\Admin\Devices\AdminClient::class);
        $mockAdminDeviceClient = \Mockery::mock(\UKFast\Admin\Devices\AdminDeviceClient::class);
        $mockCredentials = \Mockery::mock(\UKFast\Admin\Devices\Entities\Credentials::class);
        $mockAdminCredentialsClient = \Mockery::mock(\UKFast\Admin\Devices\AdminCredentialsClient::class);
        app()->bind(\UKFast\Admin\Devices\AdminClient::class, function () use ($mockAdminClient) {
            return $mockAdminClient;
        });

        // Mock the Devices API "$adminClient->devices()->getCredentials(...)->getItems()[0]" call
        $mockAdminClient->shouldReceive('devices')
            ->andReturn($mockAdminDeviceClient);
        $mockAdminDeviceClient->shouldReceive('getCredentials')
            ->withArgs([1, 1, 1, ['type' => 'API', 'user' => 'conjurerapi']])
            ->andReturn($mockCredentials);
        $mockCredentials->shouldReceive('getItems')
            ->andReturn($getItemsResponse);

        // Mock the Devices API "$adminClient->credentials()->getPassword(...)" call
        $mockAdminClient->shouldReceive('credentials')
            ->andReturn($mockAdminCredentialsClient);
        $mockAdminCredentialsClient->shouldReceive('getPassword')
            ->withArgs([1])
            ->andReturn('password');

        // Mock the Devices API "$adminClient->devices()->getCredentials(...)->getItems()[0]" call
        $mockAdminClient->shouldReceive('devices')
            ->andReturn($mockAdminDeviceClient);
        $mockAdminDeviceClient->shouldReceive('getCredentials')
            ->withArgs([1, 1, 1, ['type' => 'API', 'user' => 'ucs-api']])
            ->andReturn($mockCredentials);
        $mockCredentials->shouldReceive('getItems')
            ->andReturn([]);

        // Devices API "$adminClient->credentials()" call should never happen
        $mockAdminClient->shouldNotReceive('credentials');

        // Finally hit the UCS Info endpoint and check the expected result matches
        $this->get('/v1/hosts/' . $host->ucs_node_id . '/hardware', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertStatus(200)
            ->assertJsonFragment([
                'data' => [],
                'meta' => [],
            ]);
    }

    public function testHostWithoutServiceProfile()
    {
        $getItemsResponse = [
            0 => (object)[
                'id' => 1,
                'password' => 'password'
            ]
        ];

        // Pod, Pod location, Solution and Host setup
        Pod::factory()->create([
            'ucs_datacentre_vce_server_id' => 1,
            'ucs_datacentre_ucs_api_url' => 'http://localhost'
        ]);
        Pod\Location::factory()->create();
        Solution::factory()->create();
        $host = Host::factory()->create()->first();

        // Mock the Devices API
        $mockAdminClient = \Mockery::mock(\UKFast\Admin\Devices\AdminClient::class);
        $mockAdminDeviceClient = \Mockery::mock(\UKFast\Admin\Devices\AdminDeviceClient::class);
        $mockCredentials = \Mockery::mock(\UKFast\Admin\Devices\Entities\Credentials::class);
        $mockAdminCredentialsClient = \Mockery::mock(\UKFast\Admin\Devices\AdminCredentialsClient::class);
        app()->instance(\UKFast\Admin\Devices\AdminClient::class, $mockAdminClient);

        // Mock the Devices API "$adminClient->devices()->getCredentials(...)->getItems()[0]" call
        $mockAdminClient->shouldReceive('devices')
            ->andReturn($mockAdminDeviceClient);
        $mockAdminDeviceClient->shouldReceive('getCredentials')
            ->withArgs([1, 1, 1, ['type' => 'API', 'user' => 'conjurerapi']])
            ->andReturn($mockCredentials);
        $mockCredentials->shouldReceive('getItems')
            ->andReturn($getItemsResponse);

        // Mock the Devices API "$adminClient->credentials()->getPassword(...)" call
        $mockAdminClient->shouldReceive('credentials')
            ->andReturn($mockAdminCredentialsClient);
        $mockAdminCredentialsClient->shouldReceive('getPassword')
            ->withArgs([1])
            ->andReturn('password');

        // Mock the Devices API "$adminClient->devices()->getCredentials(...)->getItems()[0]" call
        $mockAdminClient->shouldReceive('devices')
            ->andReturn($mockAdminDeviceClient);
        $mockAdminDeviceClient->shouldReceive('getCredentials')
            ->withArgs([1, 1, 1, ['type' => 'API', 'user' => 'ucs-api']])
            ->andReturn($mockCredentials);
        $mockCredentials->shouldReceive('getItems')
            ->andReturn($getItemsResponse);

        // Mock the Devices API "$adminClient->credentials()->getPassword(...)" call
        $mockAdminClient->shouldReceive('credentials')
            ->andReturn($mockAdminCredentialsClient);
        $mockAdminCredentialsClient->shouldReceive('getPassword')
            ->withArgs([1])
            ->andReturn('password');

        // Mock the Conjurer API
        $client = \Mockery::mock(Client::class);
        app()->bind(Client::class, function ($app, $args) use ($client, $getItemsResponse) {
            $expectedUri = empty($getItemsResponse[0]->loginPort) ?
                'http://localhost' :
                'http://localhost:' . $getItemsResponse[0]->loginPort;
            $this->assertEquals($expectedUri, $args['config']['base_uri']);
            return $client;
        });

        // Mock the Conjurer API "$client->request(...)" call
        $client->shouldReceive('request')
            ->withArgs([
                'GET',
                '/api/v1/compute/' . urlencode($host->location->ucs_datacentre_location_name) .
                '/solution/' . (int)$host->ucs_node_ucs_reseller_id .
                '/node/' . urlencode($host->ucs_node_profile_id),
                [
                    'auth' => ['conjurerapi', 'password'],
                    'headers' => [
                        'X-UKFast-Compute-Username' => 'ucs-api',
                        'X-UKFast-Compute-Password' => 'password',
                        'Accept' => 'application/json',
                    ]
                ]
            ])
            ->andThrow(\Exception::class, 'Cannot find service profile with name [1-2]');

        // Finally hit the UCS Info endpoint and check the expected result matches
        $this->get('/v1/hosts/' . $host->ucs_node_id . '/hardware', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertStatus(500)
            ->assertJsonFragment([
                'errors' => [
                    'title' => 'Cannot find service profile with name [1-2]',
                    'status' => 500,
                ],
            ]);
    }
}
