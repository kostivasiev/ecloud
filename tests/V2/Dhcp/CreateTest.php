<?php

namespace Tests\V2\Dhcp;

use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreateTest extends TestCase
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
        $cloud = factory(Vpc::class)->create();
        $data = [
            'vpc_id' => $cloud->id,
        ];
        $this->post(
            '/v2/dhcps',
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

    public function testNullNameIsFailed()
    {
        $data = [
            'vpc_id' => '',
        ];
        $this->post(
            '/v2/dhcps',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The vpc id field is required',
                'status' => 422,
                'source' => 'vpc_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testNotOwnedVpcIsFailed()
    {
        $vpc = factory(Vpc::class)->create([
            'reseller_id' => 3
        ]);
        $data = [
            'vpc_id' => $vpc->getKey(),
        ];
        $this->post(
            '/v2/dhcps',
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

    public function testValidDataSucceeds()
    {
        $cloud = factory(Vpc::class)->create();
        $data = [
            'vpc_id' => $cloud->id,
        ];
        $this->post(
            '/v2/dhcps',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(201);

        $dhcpId = (json_decode($this->response->getContent()))->data->id;
        $this->seeJson([
            'id' => $dhcpId,
        ]);
    }
}
