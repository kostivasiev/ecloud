<?php

namespace Tests\V2\Dhcp;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Dhcp;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    protected Dhcp $dhcp;

    public function setUp(): void
    {
        parent::setUp();
        Model::withoutEvents(function () {
            $this->dhcp = factory(Dhcp::class)->create([
                'id' => 'dhcp-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id
            ]);
        });
    }

    public function testNoPermsIsDenied()
    {
        $this->patch(
            '/v2/dhcps/' . $this->dhcp->id,
            [
                'name' => 'Updated Name',
            ],
            []
        )
            ->seeJson([
                'title' => 'Unauthorized',
                'detail' => 'Unauthorized',
                'status' => 401,
            ])
            ->assertResponseStatus(401);
    }

    public function testNullNameIsDenied()
    {
        $this->patch(
            '/v2/dhcps/' . $this->dhcp->id,
            [
                'name' => '',
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeJson(
            [
                'title' => 'Validation Error',
                'detail' => 'The name field is required',
                'status' => 422,
                'source' => 'name'
            ]
        )
            ->assertResponseStatus(422);
    }

    public function testValidDataIsSuccessful()
    {
        Event::fake();

        $data = [
            'name' => 'Updated Name',
        ];
        $this->patch(
            '/v2/dhcps/' . $this->dhcp->id,
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertResponseStatus(202);

        $dhcp = Dhcp::findOrFail($this->dhcp->id);
        $this->assertEquals($data['name'], $dhcp->name);
    }
}
