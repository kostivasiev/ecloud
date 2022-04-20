<?php
namespace Tests\V2\Volume;

use App\Events\V2\Task\Created;
use App\Models\V2\VolumeGroup;
use App\Models\V2\Vpc;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\VolumeGroupMock;
use Tests\Mocks\Resources\VolumeMock;
use Tests\TestCase;

class MismatchVolumeGroupTest extends TestCase
{
    use VolumeMock, VolumeGroupMock;

    protected Vpc $secondaryVpc;
    protected VolumeGroup $secondaryVolumeGroup;

    public function setUp(): void
    {
        parent::setUp();
        $this->secondaryVpc = Vpc::withoutEvents(function () {
            return Vpc::factory()->create([
                'id' => 'vpc-bbbbbbbb',
            ]);
        });
        $this->secondaryVolumeGroup = VolumeGroup::withoutEvents(function () {
            return VolumeGroup::factory()->create([
                'id' => 'volgroup-bbbbbbbb',
                'name' => 'volgroup-bbbbbbbb',
                'availability_zone_id' => $this->availabilityZone()->id,
                'vpc_id' => $this->secondaryVpc->id,
            ]);
        });
    }

    public function testMismatchVolumeGroup()
    {
        Event::fake(Created::class);

        $this->asAdmin()
            ->post(
                '/v2/volumes',
                [
                    'vpc_id' => $this->vpc()->id,
                    'availability_zone_id' => $this->availabilityZone()->id,
                    'capacity' => '1',
                    'os_volume' => false,
                    'volume_group_id' => $this->secondaryVolumeGroup->id,
                ]
            )->assertJsonFragment([
                'title' => 'Validation Error',
                'detail' => 'Resources must be in the same Vpc',
            ])->assertStatus(422);
    }

    public function testAssignMismatchedSharedVolumeToVolumeGroup()
    {
        $this->volume()->setAttribute('is_shared', true)->saveQuietly();

        $this->asAdmin()
            ->patch(
                '/v2/volumes/' . $this->volume()->id,
                [
                    'volume_group_id' => $this->secondaryVolumeGroup->id,
                ]
            )->assertJsonFragment([
                'title' => 'Validation Error',
                'detail' => 'Resources must be in the same Vpc',
            ])->assertStatus(422);
    }
}
