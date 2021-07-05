<?php
namespace Tests\V2\VpnProfileGroup;

use App\Models\V2\VpnProfileGroup;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class GetTest extends TestCase
{
    protected VpnProfileGroup $vpnProfileGroup;
    
    public function setUp(): void
    {
        parent::setUp();
        $this->vpnProfileGroup = factory(VpnProfileGroup::class)->create([
            'name' => 'Profile Group Name',
            'description' => 'VPN Profile Group Description',
            'ike_profile_id' => 'ike-aaaaaaaa',
            'ipsec_profile_id' => 'ipsec-aaaaaaaa',
            'dpd_profile_id' => 'dpd-aaaaaaaa'
        ]);
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testGetCollection()
    {
        $this->get('/v2/vpn-profile-groups')
            ->seeJson(
                [
                    'name' => 'Profile Group Name',
                    'description' => 'VPN Profile Group Description',
                ]
            )
            ->assertResponseStatus(200);
    }

    public function testGetResource()
    {
        $this->get('/v2/vpn-profile-groups/' . $this->vpnProfileGroup->id)
            ->seeJson(
                [
                    'name' => 'Profile Group Name',
                    'description' => 'VPN Profile Group Description',
                ]
            )
            ->assertResponseStatus(200);
    }
}