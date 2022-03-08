<?php

namespace Tests\V2\Dhcp;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Dhcp;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class GetTest extends TestCase
{
    /** @var Dhcp */
    private $dhcp;

    public function setUp(): void
    {
        parent::setUp();
        Model::withoutEvents(function () {
            $this->dhcp = Dhcp::factory()->create([
                'id' => 'dhcp-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
            ]);
        });
    }

    public function testNoPermsIsDenied()
    {
        $this->get('/v2/dhcps')->assertJsonFragment([
            'title' => 'Unauthorized',
            'detail' => 'Unauthorized',
            'status' => 401,
        ])->assertStatus(401);
    }

    public function testGetCollection()
    {
        $this->get('/v2/dhcps', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertJsonFragment([
            'id' => $this->dhcp->id,
            'vpc_id' => $this->dhcp->vpc_id,
        ])->assertStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->get('/v2/dhcps/' . $this->dhcp->id, [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertJsonFragment([
            'id' => $this->dhcp->id,
            'vpc_id' => $this->dhcp->vpc_id,
        ])->assertStatus(200);
    }

    public function testDoesntShowNonOwnedDhcps()
    {
        $this->be(new Consumer(2, [config('app.name') . '.read']));

        $response = $this->get('/v2/dhcps');
        $response->assertStatus(200);
        $this->assertEquals(0, count($response->json()['data']));
    }

    public function testDoesntShowNonOwnedDhcp()
    {
        $this->be(new Consumer(2, [config('app.name') . '.read']));

        $response = $this->get('/v2/dhcps/' . $this->dhcp->id);
        $response->assertStatus(404);
    }
}
