<?php

namespace Tests\V2\Vpn;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use App\Models\V2\Vpn;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    protected $vpn;

    public function setUp(): void
    {
        parent::setUp();
        $this->vpn = factory(Vpn::class)->create([
            'router_id' => $this->router()->id,
        ]);
    }

    public function testSuccessfulDelete()
    {
        $this->delete(
            '/v2/vpns/' . $this->vpn->id,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);
        $vpnItem = Vpn::withTrashed()->findOrFail($this->vpn->id);
        $this->assertNotNull($vpnItem->deleted_at);
    }
}
