<?php

namespace Tests\V2\Volume;

use App\Events\V2\Task\Created;
use App\Jobs\Instance\Deploy\PrepareOsDisk;
use App\Models\V2\Volume;
use App\Rules\V2\VolumeCapacityIsGreater;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CapacityIncreaseTest extends TestCase
{
    private $volume;

    public function setUp(): void
    {
        parent::setUp();

        $this->volume = Model::withoutEvents(function () {
            return Volume::factory()->create([
                'id' => 'vol-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'iops' => '300',
                'capacity' => '100',
            ]);
        });
    }

    public function testIncreaseSize()
    {
        Event::fake([Created::class]);

        $this->volume->instances()->attach($this->instanceModel());

        $this->patch('v2/volumes/vol-test', [
            'capacity' => 200,
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertStatus(202);
    }

    public function testValidationRule()
    {
        $rule = \Mockery::mock(VolumeCapacityIsGreater::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $rule->volume = $this->volume;

        // Test with a valid value (greater than the original)
        $this->assertTrue($rule->passes('capacity', 200));

        // Test with an invalid value (less than the original)
        $this->assertFalse($rule->passes('capacity', 10));
    }

}
