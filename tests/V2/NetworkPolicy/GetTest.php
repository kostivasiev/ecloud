<?php
namespace Tests\V2\NetworkPolicy;

use App\Models\V2\NetworkPolicy;
use App\Models\V2\Network;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class GetTest extends TestCase
{
    protected NetworkPolicy $networkPolicy;
    protected Network $network;

    public function setUp(): void
    {
        parent::setUp();
        $this->networkPolicy();
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/network-policies',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )->seeJson([
            'id' => 'np-test',
            'network_id' => $this->network()->id,
            'name' => 'np-test',
        ])->assertResponseStatus(200);
    }

    public function testGetResource()
    {
        $this->get(
            '/v2/network-policies/np-test',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )->seeJson([
            'id' => 'np-test',
            'network_id' => $this->network()->id,
            'name' => 'np-test',
        ])->assertResponseStatus(200);
    }

    public function testGetHiddenNotAdminFails()
    {
        $this->router()->setAttribute('is_management', true)->save();

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        $this->get('/v2/network-policies/' . $this->networkPolicy()->id)
            ->assertResponseStatus(404);
    }

    public function testGetHiddenAdminPasses()
    {
        $this->router()->setAttribute('is_management', true)->save();

        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        $this->get('/v2/network-policies/' . $this->networkPolicy()->id)->assertResponseStatus(200);
    }
}