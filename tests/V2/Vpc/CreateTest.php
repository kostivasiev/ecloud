<?php

namespace Tests\V2\Vpc;

use App\Models\V2\Vpc;
use App\Models\V2\Region;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class CreateTest extends TestCase
{

    use DatabaseMigrations;

    protected $faker;

    protected $region;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->region = factory(Region::class)->create([
            'name'    => 'Manchester',
        ]);
    }

    public function testNoPermsIsDenied()
    {
        $data = [
            'name'    => 'Manchester DC',
        ];
        $this->post(
            '/v2/vpcs',
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

    public function testNullNameDefaultsToId()
    {
        return $this->markTestSkipped('Flushing events on VPC prevents the running of this test');

//        $data = [
//            'name'    => '',
//            'region_id' => $this->region->getKey(),
//        ];
//        $this->post(
//            '/v2/vpcs',
//            $data,
//            [
//                'X-consumer-custom-id' => '0-0',
//                'X-consumer-groups' => 'ecloud.write',
//                'X-Reseller-Id' => 1,
//            ]
//        )->assertResponseStatus(201);
//
//        $virtualPrivateCloudId = (json_decode($this->response->getContent()))->data->id;
//
//        $this->seeJson([
//            'id' => $virtualPrivateCloudId,
//        ]);
//
//        $vpc = Vpc::findOrFail($virtualPrivateCloudId);
//        $this->assertEquals($virtualPrivateCloudId, $vpc->name);
    }

    public function testNullRegionIsFailed()
    {
        $data = [
            'name'    => $this->faker->word(),
        ];
        $this->post(
            '/v2/vpcs',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
                'X-Reseller-Id' => 1,
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The region id field is required',
                'status' => 422,
                'source' => 'region_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testNotScopedFails()
    {
        $data = [
            'name'    => $this->faker->word(),
            'reseller_id' => 1,
            'region_id'    => $this->region->getKey()
        ];
        $this->post(
            '/v2/vpcs',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Bad Request',
                'detail' => 'Missing Reseller scope',
                'status' => 400,
            ])
            ->assertResponseStatus(400);
    }

    public function testValidDataSucceeds()
    {
        $data = [
            'name'    => $this->faker->word(),
            'region_id' => $this->region->getKey(),
            'reseller_id' => 1
        ];
        $this->post(
            '/v2/vpcs',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
                'X-Reseller-Id' => 1
            ]
        )
            ->seeInDatabase(
                'vpcs',
                $data,
                'ecloud'
            )
            ->assertResponseStatus(201);

        $virtualPrivateCloudId = (json_decode($this->response->getContent()))->data->id;
        $this->seeJson([
            'id' => $virtualPrivateCloudId,
        ]);
    }

}
