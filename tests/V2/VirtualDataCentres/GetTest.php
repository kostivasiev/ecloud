<?php

namespace Tests\V2\VirtualDataCentres;

use App\Models\V2\VirtualDataCentres;
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
            '/v2/vdcs',
            []
        )
            ->seeJson([
                'title'  => 'Unauthorised',
                'detail' => 'Unauthorised',
                'status' => 401,
            ])
            ->assertResponseStatus(401);
    }

    public function testGetCollection()
    {
        $virtualDataCentre = factory(VirtualDataCentres::class, 1)->create([
            'name'    => 'Manchester DC',
        ])->first();
        $this->get(
            '/v2/vdcs',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'         => $virtualDataCentre->id,
                'name'       => $virtualDataCentre->name,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetItemDetail()
    {
        $virtualDataCentre = factory(VirtualDataCentres::class, 1)->create([
            'name'    => 'Manchester DC',
        ])->first();
        $virtualDataCentre->save();
        $virtualDataCentre->refresh();

        $this->get(
            '/v2/vdcs/' . $virtualDataCentre->getKey(),
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'         => $virtualDataCentre->id,
                'name'       => $virtualDataCentre->name,
            ])
            ->assertResponseStatus(200);
    }

}
