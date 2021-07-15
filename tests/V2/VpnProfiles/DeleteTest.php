<?php
namespace Tests\V2\VpnProfiles;

use App\Models\V2\VpnProfile;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class DeleteTest extends TestCase
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
        $this->vpnProfile = factory(VpnProfile::class)->create($this->data);
    }

    public function testDeleteResourceNonAdmin()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->delete('/v2/vpn-profiles/' . $this->vpnProfile->id)
            ->seeJson([
                'title' => 'Unauthorized',
                'detail' => 'Unauthorized',
            ])->assertResponseStatus(401);
    }

    public function testDeleteResource()
    {
        $consumer = new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']);
        $consumer->setIsAdmin(true);
        $this->be($consumer);

        $this->delete('/v2/vpn-profiles/' . $this->vpnProfile->id)
            ->assertResponseStatus(204);
    }
}
