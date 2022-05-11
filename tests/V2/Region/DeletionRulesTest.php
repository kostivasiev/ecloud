<?php

namespace Tests\V2\Region;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use Faker\Factory as Faker;
use Tests\TestCase;

class DeletionRulesTest extends TestCase
{
    protected AvailabilityZone $availability_zone;
    protected Region $region;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
    }

    public function testFailedDeletion()
    {
        $this->delete(
            '/v2/regions/' . $this->region()->id,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertJsonFragment([
            'detail' => 'The specified resource has dependant relationships and cannot be deleted: ' . $this->availabilityZone()->id,
        ])->assertStatus(412);
        $region = Region::withTrashed()->findOrFail($this->region()->id);
        $this->assertNull($region->deleted_at);
    }
}
