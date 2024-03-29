<?php
namespace Tests\V2\VpnProfiles;

use App\Models\V2\VpnProfile;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class UpdateTest extends TestCase
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
    }

    public function testUpdateResourceNonAdmin()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->patch(
            '/v2/vpn-profiles/' . $this->vpnProfile->id,
            [
                'name' => 'Vpn Test Profile (Updated)',
            ]
        )
            ->assertJsonFragment([
                'title' => 'Unauthorized',
                'detail' => 'Unauthorized',
            ])->assertStatus(401);
    }

    public function testUpdateResource()
    {
        $consumer = new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']);
        $consumer->setIsAdmin(true);
        $this->be($consumer);

        $data = [
            'name' => 'Vpn Test Profile',
            'ike_version' => 'ike_v2',
            'encryption_algorithm' => [
                'aes gcm 128',
            ],
            'digest_algorithm' => [
                'sha2 384',
            ],
            'diffie_hellman' => [
                'group 2',
            ],
        ];

        $transformed = $data;
        $transformed['id'] = $this->vpnProfile->id;
        $transformed['encryption_algorithm'] = implode(',', $data['encryption_algorithm']);
        $transformed['digest_algorithm'] = implode(',', $data['digest_algorithm']);
        $transformed['diffie_hellman'] = implode(',', $data['diffie_hellman']);

        $this->patch('/v2/vpn-profiles/' . $this->vpnProfile->id, $data)
            ->assertStatus(200);
        $this->assertDatabaseHas('vpn_profiles', $transformed, 'ecloud');
    }
}
