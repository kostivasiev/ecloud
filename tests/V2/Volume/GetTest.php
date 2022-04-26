<?php

namespace Tests\V2\Volume;

use App\Models\V2\Volume;
use Tests\Mocks\Resources\VolumeMock;
use Tests\TestCase;

class GetTest extends TestCase
{
    use VolumeMock;

    public function setUp(): void
    {
        parent::setUp();

        Volume::withoutEvents(function () {
            $this->volume = Volume::factory()->create([
                'id' => 'vol-test',
                'name' => 'Volume',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'vmware_uuid' => 'uuid-test-uuid-test-uuid-test'
            ]);
        });
    }

    public function testGetCollection()
    {
        $this->get('/v2/volumes', [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertJsonFragment([
            'id' => 'vol-test',
            'name' => 'Volume',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'capacity' => 100,
        ])->assertJsonMissing([
            'vmware_uuid' => 'uuid-test-uuid-test-uuid-test'
        ])->assertStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->get('/v2/volumes/vol-test', [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertJsonFragment([
            'id' => 'vol-test',
            'name' => 'Volume',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'capacity' => 100,
        ])->assertJsonMissing([
            'vmware_uuid' => 'uuid-test-uuid-test-uuid-test'
        ])->assertStatus(200);
    }

    public function testGetItemDetailAdmin()
    {
        $this->get('/v2/volumes/vol-test', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertJsonFragment([
            'id' => 'vol-test',
            'name' => 'Volume',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'capacity' => 100,
            'vmware_uuid' => 'uuid-test-uuid-test-uuid-test',
        ])->assertStatus(200);
    }

    public function testFilterCollectionByAttached()
    {
        $this->instanceModel()->volumes()->attach($this->volume());

        // First way of doing this
        $this->asUser()
            ->get('/v2/volumes?attached:eq=true')
            ->assertJsonFragment([
                'id' => $this->volume()->id,
                'attached' => true,
            ])
            ->assertStatus(200);

        // Second way of doing this
        $this->asUser()
            ->get('/v2/volumes?attached:neq=false')
            ->assertJsonFragment([
                'id' => $this->volume()->id,
                'attached' => true,
            ])
            ->assertStatus(200);
    }

    public function testFilterCollectionByUnattached()
    {
        // First way of doing this
        $this->asUser()
            ->get('/v2/volumes?attached:eq=false')
            ->assertJsonFragment([
                'id' => $this->volume()->id,
                'attached' => false,
            ])
            ->assertStatus(200);

        // Second way of doing this
        $this->asUser()
            ->get('/v2/volumes?attached:neq=true')
            ->assertJsonFragment([
                'id' => $this->volume()->id,
                'attached' => false,
            ])
            ->assertStatus(200);
    }
}
