<?php

namespace Tests\V2\Volume;

use App\Models\V2\Volume;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
{
    use DatabaseMigrations;

    protected $volume;

    public function setUp(): void
    {
        parent::setUp();

        Volume::withoutEvents(function () {
            $this->volume = factory(Volume::class)->create([
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
        ])->seeJson([
            'id' => 'vol-test',
            'name' => 'Volume',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'capacity' => '100',
        ])->dontSeeJson([
            'vmware_uuid' => 'uuid-test-uuid-test-uuid-test'
        ])->assertResponseStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->get('/v2/volumes/vol-test', [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => 'vol-test',
            'name' => 'Volume',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'capacity' => '100',
        ])->dontSeeJson([
            'vmware_uuid' => 'uuid-test-uuid-test-uuid-test'
        ])->assertResponseStatus(200);
    }

    public function testGetItemDetailAdmin()
    {
        $this->get('/v2/volumes/vol-test', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => 'vol-test',
            'name' => 'Volume',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'capacity' => '100',
            'vmware_uuid' => 'uuid-test-uuid-test-uuid-test',
        ])->assertResponseStatus(200);
    }
}
