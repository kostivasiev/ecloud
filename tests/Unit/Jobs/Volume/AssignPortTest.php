<?php

namespace Tests\Unit\Jobs\Volume;

use App\Jobs\Volume\AssignPort;
use App\Models\V2\Volume;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\VolumeGroupMock;
use Tests\TestCase;

class AssignPortTest extends TestCase
{
    use VolumeGroupMock;

    public function testNoVolumeGroupAssignedIsSkipped()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        $volume =  Volume::factory()->create([
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
        ]);

        dispatch(new AssignPort($volume));

        Event::assertDispatched(JobProcessed::class);
    }

    public function testPortAlreadyAssignedIsSkipped()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        $volume =  Volume::factory()->sharedVolume($this->volumeGroup()->id)->create([
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
        ]);

        dispatch(new AssignPort($volume));

        Event::assertDispatched(JobProcessed::class);

        $this->assertEquals(1, $volume->refresh()->port);
    }

    public function testAssignNextAvailablePortSuccess()
    {
        Event::fake([JobFailed::class]);

        $volume =  Volume::factory()->sharedVolume($this->volumeGroup()->id)->create([
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'port' => null
        ]);

        dispatch(new AssignPort($volume));

        Event::assertNotDispatched(JobFailed::class);

        $this->assertNotNull($volume->refresh()->port);

        $this->assertEqualsCanonicalizing(0, $volume->port);
    }

    public function testScsiControllerReservedPortIsNotAssigned()
    {
        Event::fake([JobFailed::class]);

        Config::set('volume-group.scsi_controller_reserved_port', 7);

        Volume::factory()
            ->sharedVolume($this->volumeGroup()->id)
            ->count(7)
            ->state(new Sequence(
                ['port' => 0],
                ['port' => 1],
                ['port' => 2],
                ['port' => 3],
                ['port' => 4],
                ['port' => 5],
                ['port' => 6],
            ))
            ->create([
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
            ]);

        $volume =  Volume::factory()->sharedVolume($this->volumeGroup()->id)->create([
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'port' => null
        ]);

        dispatch(new AssignPort($volume));

        Event::assertNotDispatched(JobFailed::class);

        $this->assertNotNull($volume->refresh()->port);

        $this->assertEqualsCanonicalizing(8, $volume->port);
    }

    public function testAssignPortNoPortsAvailableFails()
    {
        Event::fake([JobFailed::class]);

        Config::set('volume-group.scsi_controller_reserved_port', 7);
        Config::set('volume-group.max_ports', 10);

        Volume::factory()
            ->sharedVolume($this->volumeGroup()->id)
            ->count(10)
            ->state(new Sequence(
                ['port' => 0],
                ['port' => 1],
                ['port' => 2],
                ['port' => 3],
                ['port' => 4],
                ['port' => 5],
                ['port' => 6],
                ['port' => 8],
                ['port' => 9],
                ['port' => 10],
            ))
            ->create([
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
            ]);

        $volume =  Volume::factory()->sharedVolume($this->volumeGroup()->id)->create([
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'port' => null
        ]);

        dispatch(new AssignPort($volume));

        Event::assertDispatched(JobFailed::class);
    }
}
