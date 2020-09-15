<?php

namespace Tests\V2\Volume;

use App\Models\V2\Region;
use App\Models\V2\Volume;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class GetTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;

    protected $vpc;

    protected $router;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->region = factory(Region::class)->create();
        $this->vpc = factory(Vpc::class)->create([
            'name' => 'Manchester DC',
            'region_id' => $this->region->getKey()
        ]);

        $this->volume = factory(Volume::class)->create([
            'name'       => 'Volume 1',
            'vpc_id' => $this->vpc->getKey()
        ]);
    }
    
    public function testGetCollection()
    {
        $this->get(
            '/v2/volumes',
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'         => $this->volume->getKey(),
                'name'       => $this->volume->name,
                'vpc_id'       => $this->volume->vpc_id,
                'capacity'       => $this->volume->capacity,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->get(
            '/v2/volumes/' . $this->volume->getKey(),
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'         => $this->volume->id,
                'name'       => $this->volume->name,
                'vpc_id'       => $this->volume->vpc_id
            ])
            ->dontSeeJson(
                [
                    'vmware_uuid' => '03747ccf-d56b-45a9-b589-177f3cb9936e'
                ]
            )
            ->assertResponseStatus(200);
    }

    public function testGetItemDetailAdmin()
    {
        $this->get(
            '/v2/volumes/' . $this->volume->getKey(),
            [
                'X-consumer-custom-id' => 'o-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'         => $this->volume->id,
                'name'       => $this->volume->name,
                'vpc_id'       => $this->volume->vpc_id,
                'vmware_uuid' => '03747ccf-d56b-45a9-b589-177f3cb9936e'
            ])
            ->assertResponseStatus(200);
    }

}
