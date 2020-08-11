<?php

namespace Tests\V2\Vpc;

use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class GetTest extends TestCase
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
        $this->get(
            '/v2/vpcs',
            []
        )
            ->seeJson([
                'title'  => 'Unauthorised',
                'detail' => 'Unauthorised',
                'status' => 401,
            ])
            ->assertResponseStatus(401);
    }

    public function testGetCollectionAdmin()
    {
        $virtualPrivateCloud = factory(Vpc::class, 1)->create([
            'name'    => 'Manchester DC',
        ])->first();
        $this->get(
            '/v2/vpcs',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'         => $virtualPrivateCloud->id,
                'name'       => $virtualPrivateCloud->name,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetCollectionResellerScope()
    {
        $vpc = factory(Vpc::class, 1)->create([
            'reseller_id' => 2,
        ])->first();

        $this->get(
            '/v2/vpcs',
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->dontSeeJson([
                'id'         => $vpc->getKey(),
                'name'       => $vpc->name,
            ])
            ->assertResponseStatus(200);

        $this->get(
            '/v2/vpcs',
            [
                'X-consumer-custom-id' => '2-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'         => $vpc->getKey(),
                'name'       => $vpc->name,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetCollectionAdminResellerScope()
    {
        $vpc1 = factory(Vpc::class, 1)->create([
            'reseller_id' => 1,
        ])->first();

        $vpc2 = factory(Vpc::class, 1)->create([
            'reseller_id' => 2,
        ])->first();

        $this->get(
            '/v2/vpcs',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
                'X-Reseller-Id' => 1
            ]
        )
            ->dontSeeJson([
                'id'         => $vpc2->getKey(),
            ])
            ->seeJson([
                'id'         => $vpc1->getKey(),
            ])
            ->assertResponseStatus(200);
    }

    public function testNonMatchingResellerIdFails()
    {
        $vdc = factory(Vpc::class, 1)->create(['reseller_id' => 3])->first();
        $this->get(
            '/v2/vpcs/' . $vdc->getKey(),
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

    public function testGetItemDetail()
    {
        $virtualPrivateCloud = factory(Vpc::class, 1)->create([
            'name'    => 'Manchester DC',
        ])->first();
        $virtualPrivateCloud->save();
        $virtualPrivateCloud->refresh();

        $this->get(
            '/v2/vpcs/' . $virtualPrivateCloud->getKey(),
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'         => $virtualPrivateCloud->id,
                'name'       => $virtualPrivateCloud->name,
            ])
            ->assertResponseStatus(200);
    }

}
