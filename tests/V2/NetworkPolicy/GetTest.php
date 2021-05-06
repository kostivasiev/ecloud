<?php
namespace Tests\V2\NetworkPolicy;

use App\Models\V2\NetworkPolicy;
use App\Models\V2\Network;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

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
}