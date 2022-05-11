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
        $this->vpnProfileGroup = VpnProfileGroup::factory()->create([
            'name' => 'Profile Group Name',
            'description' => 'VPN Profile Group Description',
            'availability_zone_id' => $this->availabilityZone()->id,
            'ike_profile_id' => 'ike-aaaaaaaa',
            'ipsec_profile_id' => 'ipsec-aaaaaaaa'
        ]);
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testGetCollection()
    {
        $this->get('/v2/vpn-profile-groups')
            ->assertJsonFragment(
                [
                    'name' => 'Profile Group Name',
                    'description' => 'VPN Profile Group Description',
                ]
            )
            ->assertStatus(200);
    }

    public function testGetResource()
    {
        $this->get('/v2/vpn-profile-groups/' . $this->vpnProfileGroup->id)
            ->assertJsonFragment(
                [
                    'name' => 'Profile Group Name',
                    'description' => 'VPN Profile Group Description',
                ]
            )
            ->assertStatus(200);
    }

    public function testIsNotVisibleInNonPublicAZ()
    {
        $this->availabilityZone()->is_public = false;
        $this->availabilityZone()->saveQuietly();

        $this->get('/v2/vpn-profile-groups')
            ->assertJsonMissing(
                [
                    'id' => $this->vpnProfileGroup->id,
                ]
            )->assertStatus(200);
    }

    public function testIsNotVisibleInNonPublicRegion()
    {
        $this->region()->is_public = false;
        $this->region()->saveQuietly();

        $this->get('/v2/vpn-profile-groups')
            ->assertJsonMissing(
                [
                    'id' => $this->vpnProfileGroup->id,
                ]
            )->assertStatus(200);
    }
}