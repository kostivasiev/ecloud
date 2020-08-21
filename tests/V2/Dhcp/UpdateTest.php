<?php

namespace Tests\V2\Dhcp;

use App\Models\V2\Dhcp;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
    }

    public function testNoPermsIsDenied()
    {
        $vpc = factory(Vpc::class)->create();
        $dhcp = factory(Dhcp::class)->create([
            'vpc_id' => $vpc->id,
        ]);
        $data = [
            'vpc_id' => $vpc->id,
        ];
        $this->patch(
            '/v2/dhcps/' . $dhcp->getKey(),
            $data,
            []
        )
            ->seeJson([
                'title'  => 'Unauthorised',
                'detail' => 'Unauthorised',
                'status' => 401,
            ])
            ->assertResponseStatus(401);
    }

    public function testNullNameIsDenied()
    {
        $vpc = factory(Vpc::class)->create();
        $dhcp = factory(Dhcp::class)->create([
            'vpc_id' => $vpc->id,
        ]);
        $data = [
            'vpc_id'    => '',
        ];
        $this->patch(
            '/v2/dhcps/' . $dhcp->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The vpc id field, when specified, cannot be null',
                'status' => 422,
                'source' => 'vpc_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testNotOwnedVpcIsFailed()
    {
        $vpc = factory(Vpc::class)->create([
            'reseller_id' => 1
        ]);
        $vpc2 = factory(Vpc::class)->create([
            'reseller_id' => 3
        ]);
        $dhcp = factory(Dhcp::class)->create([
            'vpc_id' => $vpc->id,
        ]);
        $data = [
            'vpc_id'    => $vpc2->getKey(),
        ];
        $this->patch(
            '/v2/dhcps/' . $dhcp->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The specified vpc id was not found',
                'status' => 422,
                'source' => 'vpc_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testValidDataIsSuccessful()
    {
        $vpc = factory(Vpc::class)->create();
        $dhcp = factory(Dhcp::class)->create([
            'vpc_id' => $vpc->id,
        ]);
        $data = [
            'vpc_id' => $vpc->id,
        ];
        $this->patch(
            '/v2/dhcps/' . $dhcp->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertResponseStatus(200);

        $dhcp = Dhcp::findOrFail($dhcp->getKey());
        $this->assertEquals($data['vpc_id'], $dhcp->vpc_id);
    }
}
