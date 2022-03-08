<?php

namespace Tests\V2\AvailabilityZone;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Credential;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetCredentialsTest extends TestCase
{
    protected \Faker\Generator $faker;
    protected AvailabilityZone $availabilityZone;
    protected Credential $credential;
    protected Region $region;
    protected Vpc $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->region = Region::factory()->create();
        $this->availabilityZone = AvailabilityZone::factory()->create([
            'region_id' => $this->region->id
        ]);
        $this->credential = Credential::factory()->create([
            'resource_id' => $this->availabilityZone->id,
        ]);
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/availability-zones/' . $this->availabilityZone->id . '/credentials',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->assertJsonFragment([
                'id' => $this->credential->id,
                'resource_id' => $this->credential->resource_id,
                'host' => $this->credential->host,
                'username' => $this->credential->username,
                'password' => $this->credential->password,
                'port' => $this->credential->port,
            ])
            ->assertStatus(200);
    }
}
