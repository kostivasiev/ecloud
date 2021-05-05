<?php

namespace Tests\V2\Vpn;

use App\Models\V2\Vpn;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    protected $vpn;

    public function setUp(): void
    {
        parent::setUp();

        $this->vpn = factory(Vpn::class)->create([
            'router_id' => $this->router()->id,
        ]);
    }

    public function testNotOwnedRouterResourceIsFailed()
    {
        $this->vpc()->reseller_id = 3;
        $this->vpc()->saveQuietly();

        $this->patch(
            '/v2/vpns/' . $this->vpn->id,
            [
                'router_id' => $this->router()->id,
            ],
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title' => 'Validation Error',
                'detail' => 'The specified router id was not found',
                'status' => 422,
                'source' => 'router_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testValidDataIsSuccessful()
    {
        $data = [
            'router_id' => $this->router()->id,
        ];
        $this->patch(
            '/v2/vpns/' . $this->vpn->id,
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertResponseStatus(200);

        $vpnItem = Vpn::findOrFail($this->vpn->id);
        $this->assertEquals($data['router_id'], $vpnItem->router_id);
    }
}
