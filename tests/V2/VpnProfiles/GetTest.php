<?php
namespace Tests\V2\VpnProfiles;

use App\Models\V2\VpnProfile;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class GetTest extends TestCase
{
    protected VpnProfile $vpnProfile;
    protected array $data;

    public function setUp(): void
    {
        parent::setUp();
        $this->data = [
            'name' => 'Vpn Test Profile',
            'ike_version' => 'ike_v2',
            'encryption_algorithm' => [
                'aes 128',
                'aes 256',
            ],
            'digest_algorithm' => [
                'sha2 256',
            ],
            'diffie_hellman' => [
                'group 14',
            ],
        ];
        $this->vpnProfile = VpnProfile::factory()->create($this->data);
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testGetCollection()
    {
        $this->get('/v2/vpn-profiles')
            ->assertJsonFragment($this->data)
            ->assertStatus(200);
    }

    public function testGetResource()
    {
        $this->get('/v2/vpn-profiles/' . $this->vpnProfile->id)
            ->assertJsonFragment($this->data)
            ->assertStatus(200);
    }
}
