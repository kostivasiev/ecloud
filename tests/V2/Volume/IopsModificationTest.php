<?php

namespace Tests\V2\Volume;

use App\Events\V2\Task\Created;
use App\Models\V2\Volume;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class IopsModificationTest extends TestCase
{
    protected Volume $volume;

    public function setUp(): void
    {
        parent::setUp();

        $this->volume = Volume::factory()->create([
            'id' => 'vol-test',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
        ]);
    }

    public function testSetValidIopsValue()
    {
        Event::fake([Created::class]);

        $this->instanceModel()->volumes()->attach($this->volume);

        $this->patch('/v2/volumes/' . $this->volume->id, [
            'iops' => 600,
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertStatus(202);
        $this->assertDatabaseHas('volumes', [
            'id' => $this->volume->id,
            'iops' => 600,
        ],'ecloud');
    }

    public function testSetInvalidIopsValue()
    {
        $this->instanceModel()->volumes()->attach($this->volume);

        $this->patch('/v2/volumes/' . $this->volume->id, [
            'iops' => 200,
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertJsonFragment([
            'title' => 'Validation Error',
            'detail' => 'The specified iops field is not a valid IOPS value (300, 600, 1200, 2500)',
            'source' => 'iops',
        ])->assertStatus(422);
    }
}
