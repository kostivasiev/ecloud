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
        $this->vpnProfileGroup = factory(VpnProfileGroup::class)->create([
            'name' => 'Profile Group Name',
            'description' => 'VPN Profile Group Description',
            'ike_profile_id' => 'ike-aaaaaaaa',
            'ipsec_profile_id' => 'ipsec-aaaaaaaa',
            'dpd_profile_id' => 'dpd-aaaaaaaa'
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
        )->seeJson(
            [
                'title' => 'Unauthorized',
                'detail' => 'Unauthorized',
            ]
        )->assertResponseStatus(401);
    }

    public function testUpdateResourceAsAdmin()
    {
        $consumer = new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']);
        $consumer->setIsAdmin(true);
        $this->be($consumer);

        $this->patch(
            '/v2/vpn-profile-groups/' . $this->vpnProfileGroup->id,
            $this->data,
        )->seeInDatabase(
            'vpn_profile_groups',
            array_merge(['id' => $this->vpnProfileGroup->id], $this->data),
            'ecloud'
        )->assertResponseStatus(200);
    }

}