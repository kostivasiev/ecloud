<?php
namespace Tests\V2\VpnProfileGroup;

use App\Models\V2\VpnProfileGroup;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class UpdateTest extends TestCase
{
    protected VpnProfileGroup $vpnProfileGroup;
    protected array $data;

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

        $this->data = [
            'name' => 'Updated Name',
            'description' => 'Updated Description',
        ];
    }

    public function testUpdateResourceAsNonAdmin()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->patch(
            '/v2/vpn-profile-groups/' . $this->vpnProfileGroup->id,
            $this->data,
        )->assertJsonFragment(
            [
                'title' => 'Unauthorized',
                'detail' => 'Unauthorized',
            ]
        )->assertStatus(401);
    }

    public function testUpdateResourceAsAdmin()
    {
        $consumer = new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']);
        $consumer->setIsAdmin(true);
        $this->be($consumer);

        $this->patch(
            '/v2/vpn-profile-groups/' . $this->vpnProfileGroup->id,
            $this->data,
        )->assertStatus(200);
        $this->assertDatabaseHas(
            'vpn_profile_groups',
            array_merge(['id' => $this->vpnProfileGroup->id], $this->data),
            'ecloud'
        );
    }

}