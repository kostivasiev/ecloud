<?php
namespace Tests\V2\VpnProfileGroup;

use App\Models\V2\VpnProfileGroup;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class DeleteTest extends TestCase
{
    protected VpnProfileGroup $vpnProfileGroup;

    public function setUp(): void
    {
        parent::setUp();
        $this->vpnProfileGroup = factory(VpnProfileGroup::class)->create([
            'name' => 'Profile Group Name',
            'description' => 'VPN Profile Group Description',
            'availability_zone_id' => $this->availabilityZone()->id,
            'ike_profile_id' => 'ike-aaaaaaaa',
            'ipsec_profile_id' => 'ipsec-aaaaaaaa'
        ]);
    }

    public function testDeleteAsNonAdmin()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->delete('/v2/vpn-profile-groups/' . $this->vpnProfileGroup->id)
            ->seeJson(
                [
                    'title' => 'Unauthorized',
                    'detail' => 'Unauthorized',
                ]
            )->assertResponseStatus(401);
    }

    public function testDeleteAsAdmin()
    {
        $consumer = new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']);
        $consumer->setIsAdmin(true);
        $this->be($consumer);

        $this->delete('/v2/vpn-profile-groups/' . $this->vpnProfileGroup->id)
            ->assertResponseStatus(204);
    }
}