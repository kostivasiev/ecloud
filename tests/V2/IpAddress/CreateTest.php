<?php
namespace Tests\V2\IpAddress;

use App\Models\V2\IpAddress;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CreateTest extends TestCase
{
    public function setUp():void
    {
        parent::setUp();
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
    }

    public function testValidDataSucceeds()
    {
        $data = [
            'name' => 'Test',
            'ip_address' => '10.0.0.4',
            'network_id' => $this->network()->id,
        ];

        $this->post('/v2/ip-addresses', $data)
            ->assertStatus(202);
        $this->assertDatabaseHas(
            'ip_addresses',
            array_merge($data, ['type' => IpAddress::TYPE_CLUSTER]),
            'ecloud'
        );
    }

    public function testSuccessNotAdmin()
    {
        $this->be((new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(false));

        $data = [
            'name' => 'Test',
            'ip_address' => '10.0.0.4',
            'network_id' => $this->network()->id
        ];

        $this->post('/v2/ip-addresses', $data)
            ->assertStatus(202);
        $this->assertDatabaseHas(
            'ip_addresses',
            array_merge($data, ['type' => IpAddress::TYPE_CLUSTER]),
            'ecloud'
        );
    }

    public function testIpAddressNotInSubnetFails()
    {
        $data = [
            'name' => 'Test',
            'ip_address' => '1.1.1.1',
            'network_id' => $this->network()->id
        ];

        $this->post('/v2/ip-addresses', $data)->assertStatus(422);
    }

    public function testAutoAllocatedIpAddress()
    {
        $data = [
            'name' => 'Test',
            'network_id' => $this->network()->id,
        ];
        $response = $this->post(
            '/v2/ip-addresses',
            $data
        )->assertStatus(202);

        $this->assertDatabaseHas(IpAddress::class, $data, 'ecloud');

        $ipAddressId = (json_decode($response->getContent()))->data->id;
        $ipAddress = IpAddress::findOrFail($ipAddressId);

        $this->assertNotNull($ipAddress->ip_address);
    }

    public function testCacheLockFailedDuringAutoAllocation()
    {
        Cache::shouldReceive('lock')->withAnyArgs()->andReturnSelf();
        Cache::shouldReceive('block')->withAnyArgs()->andThrow(new LockTimeoutException());
        Cache::shouldReceive('release')->andReturnTrue();

        $this->post(
            '/v2/ip-addresses',
            [
                'name' => 'Test',
                'network_id' => $this->network()->id,
                'type' => IpAddress::TYPE_DHCP,
            ]
        )->assertJsonFragment([
            'title' => 'Failed',
            'detail' => 'Failed to automatically assign an ip address',
        ])->assertStatus(424);
    }

    public function testCacheLockFailedDuringValidation()
    {
        Cache::shouldReceive('lock')->withAnyArgs()->andReturnSelf();
        Cache::shouldReceive('block')->withAnyArgs()->andThrow(new LockTimeoutException());
        Cache::shouldReceive('release')->andReturnTrue();

        $this->post(
            '/v2/ip-addresses',
            [
                'name' => 'Test',
                'ip_address' => '10.0.0.5',
                'network_id' => $this->network()->id,
                'type' => IpAddress::TYPE_DHCP,
            ]
        )->assertJsonFragment([
            'title' => 'Validation Failure',
            'detail' => 'Failed to check ip address availability',
        ])->assertStatus(424);
    }
}
