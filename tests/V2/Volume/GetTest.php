<?php

namespace Tests\V2\Volume;

use App\Models\V2\Volume;
use Tests\TestCase;

class GetTest extends TestCase
{
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
}
