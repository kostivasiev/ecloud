<?php

namespace Tests\V2\Vpc;

use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class UpdateTest extends TestCase
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
        $vpc = factory(Vpc::class)->create();
        $data = [
            'name'    => 'Manchester DC',
            'region_id'    => $this->region->getKey(),
        ];
        $this->patch(
            '/v2/vpcs/' . $vpc->getKey(),
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
        $data = [
            'name'    => '',
            'region_id'    => $this->region->getKey(),
        ];
        $this->patch(
            '/v2/vpcs/' . $vpc->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The name field, when specified, cannot be null',
                'status' => 422,
                'source' => 'name'
            ])
            ->assertResponseStatus(422);
    }

    public function testNullRegionIsDenied()
    {
        $vpc = factory(Vpc::class)->create();
        $data = [
            'name'    => $this->faker->word(),
            'region_id'    => '',
        ];
        $this->patch(
            '/v2/vpcs/' . $vpc->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The region id field, when specified, cannot be null',
                'status' => 422,
                'source' => 'region_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testNonMatchingResellerIdFails()
    {
        $vpc = factory(Vpc::class)->create(['reseller_id' => 3]);
        $data = [
            'name'    => 'Manchester DC',
            'region_id'    => $this->region->getKey(),
        ];
        $this->patch(
            '/v2/vpcs/' . $vpc->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Not found',
                'detail' => 'No Vpc with that ID was found',
                'status' => 404,
            ])
            ->assertResponseStatus(404);
    }

    public function testValidDataIsSuccessful()
    {
        $vpc = factory(Vpc::class)->create();
        $data = [
            'name'    => $this->faker->word(),
            'reseller_id' => 2,
            'region_id'    => $this->region->getKey(),
        ];
        $this->patch(
            '/v2/vpcs/' . $vpc->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeInDatabase(
                'vpcs',
                $data,
                'ecloud'
            )
            ->assertResponseStatus(200);

        $virtualPrivateCloud = Vpc::findOrFail($vpc->getKey());
        $this->assertEquals($data['name'], $virtualPrivateCloud->name);
    }
}
