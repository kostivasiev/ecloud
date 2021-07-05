<?php
namespace Tests\V2\VpnProfileGroup;

use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CreateTest extends TestCase
{
    public function testCreateResourceNonAdmin()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->post(
            '/v2/vpn-profile-groups',
            [
                'name' => 'Profile Group Name',
                'description' => 'VPN Profile Group Description',
                'ike_profile_id' => 'ike-aaaaaaaa',
                'ipsec_profile_id' => 'ipsec-aaaaaaaa',
                'dpd_profile_id' => 'dpd-aaaaaaaa'
            ]
        )->seeJson(
            [
                'title' => 'Unauthorized',
                'detail' => 'Unauthorized',
            ]
        )->assertResponseStatus(401);
    }

    public function testCreateResourceAsAdmin()
    {
        $consumer = new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']);
        $consumer->setIsAdmin(true);
        $this->be($consumer);

        $this->post(
            '/v2/vpn-profile-groups',
            [
                'name' => 'Profile Group Name',
                'description' => 'VPN Profile Group Description',
                'ike_profile_id' => 'ike-aaaaaaaa',
                'ipsec_profile_id' => 'ipsec-aaaaaaaa',
                'dpd_profile_id' => 'dpd-aaaaaaaa'
            ]
        )->assertResponseStatus(201);
    }
}