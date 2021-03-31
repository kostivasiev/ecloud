<?php

namespace Tests\V2\Region;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetVpcsTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected $regions;
    protected $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->regions = factory(Region::class, 2)->create([
            'name' => $this->faker->country(),
        ])->each(function ($region) {
            factory(AvailabilityZone::class, 2)->create([
                'region_id' => $region->id,
                'name'      => $this->faker->city(),
                'is_public' => false,
            ]);
        });
        Model::withoutEvents(function() {
            $this->vpc = factory(Vpc::class)->create([
                'id' => 'vpc-test',
                'name'      => 'VPC '.uniqid(),
                'region_id' => $this->regions[0]->id,
            ]);
        });
    }

    public function testGetVpcCollection()
    {
        $this->get(
            '/v2/regions/'.$this->regions[0]->id.'/vpcs',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'        => $this->vpc()->id,
                'name'      => $this->vpc()->name,
                'region_id' => $this->vpc()->region_id,
            ])
            ->assertResponseStatus(200);
    }
}
