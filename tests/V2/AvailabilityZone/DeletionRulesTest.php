<?php

namespace Tests\V2\AvailabilityZone;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Router;
use Faker\Factory as Faker;
use Tests\TestCase;

class DeletionRulesTest extends TestCase
{
    protected $faker;
    protected $availabilityZone;
    protected $region;
    protected $router;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->region = Region::factory()->create();
        $this->availabilityZone = AvailabilityZone::factory()->create([
            'region_id' => $this->region->id
        ]);
        $this->router = Router::factory()->create([
            'name' => 'Manchester Router 1',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone->id,
        ]);
    }

    public function testFailedDeletion()
    {
        $this->delete(
            '/v2/availability-zones/'.$this->availabilityZone->id,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertJsonFragment([
            'detail' => 'The specified resource has dependant relationships and cannot be deleted: ' . $this->router->id,
        ])->assertStatus(412);
        $availabilityZone = AvailabilityZone::withTrashed()->findOrFail($this->availabilityZone->id);
        $this->assertNull($availabilityZone->deleted_at);
    }
}
