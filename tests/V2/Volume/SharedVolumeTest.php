<?php

namespace Tests\V2\Volume;

use App\Events\V2\Task\Created;
use App\Models\V2\Volume;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\VolumeGroupMock;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class SharedVolumeTest extends TestCase
{
    use VolumeGroupMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    /**
     * Confirm volume group has free ports when adding volume
     */
    public function testCreateSharedVolumeVolumeGroupDoesNotHaveAvailablePortsFails()
    {
        Config::set('volume-group.max_ports', 1);

        Volume::factory()->create([
            'vpc_id' => $this->vpc()->id,
            'volume_group_id' => $this->volumeGroup()->id
        ]);

        $this->post('/v2/volumes', [
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'capacity' => '1',
            'volume_group_id' => $this->volumeGroup()->id
        ])->assertJsonFragment(
            [
                'title' => 'Validation Error',
                'detail' => 'Maximum port count reached for the specified volume group',
            ]
        )->assertStatus(422);
    }

    public function testUpdateSharedVolumeVolumeGroupDoesNotHaveAvailablePortsFails()
    {
        Event::fake(Created::class);
        Config::set('volume-group.max_ports', 1);

        Volume::factory()
            ->sharedVolume($this->volumeGroup()->id)
            ->create([
            'vpc_id' => $this->vpc()->id,
        ]);

        $volume = Volume::factory()->create([
            'vpc_id' => $this->vpc()->id
        ]);

        $this->patch('/v2/volumes/' . $volume->id, [
            'volume_group_id' => $this->volumeGroup()->id
        ])->assertJsonFragment(
            [
                'title' => 'Validation Error',
                'detail' => 'Maximum port count reached for the specified volume group',
            ]
        )->assertStatus(422);
    }

    /**
     * I add a volume to a volume group - the volume is an OS disk - an error is thrown
     */
    public function testAddToVolumeGroupIsOsDiskFails()
    {
        $volume = Volume::factory()->osVolume()->create([
            'vpc_id' => $this->vpc()->id,
        ]);

        $this->patch('/v2/volumes/' . $volume->id, [
            'volume_group_id' => $this->volumeGroup()->id
        ])->assertJsonFragment(
            [
                'title' => 'Validation Error',
                'detail' => 'Operating System volumes can not be used as shared volumes',
            ]
        )->assertStatus(422);
    }

    public function testAddToVolumeGroupIsNotOsDiskSucceeds()
    {
        Event::fake([Created::class]);
        $volume = Volume::factory()->sharedVolume()->create([
            'vpc_id' => $this->vpc()->id,
        ]);

        $this->patch('/v2/volumes/' . $volume->id, [
            'volume_group_id' => $this->volumeGroup()->id
        ])->assertStatus(202);
    }


    /**
     * I add a volume to a volume group - The volume is part of a volume group - An error is thrown
     */
    public function testVolumeIsAlreadyAssignedToVolumeGroupThrowsError()
    {
        Event::fake([Created::class]);

        $volume = Volume::factory()
            ->sharedVolume('volgroup-' . uniqid())
            ->create([
                'id' => 'vol-test',
                'vpc_id' => $this->vpc()->id,
            ]);

        $this->patch(
            '/v2/volumes/' . $volume->id,
            [
                'volume_group_id' => $this->volumeGroup()->id,
            ]
        )->assertJsonFragment([
            'title' => 'Validation Error',
            'detail' => 'The volume is already a member of a volume group',
            'status' => 422,
            'source' => 'volume_group_id'
        ])
            ->assertStatus(422);
    }

    public function testVolumeIsNotAlreadyAssignedToVolumeGroupSucceeds()
    {
        Event::fake([Created::class]);

        $volume = Volume::factory()
            ->sharedVolume()
            ->create([
                'vpc_id' => $this->vpc()->id,
            ]);

        $this->patch(
            '/v2/volumes/' . $volume->id,
            [
                'volume_group_id' => $this->volumeGroup()->id,
            ]
        )->assertStatus(202);
    }

    /**
     * I add a volume to a volume group - the volume is not a shared disk - an error is thrown
     */
    public function testAddToVolumeGroupIsNotSharedFails()
    {
        $volume = Volume::factory()->osVolume()->create([
            'vpc_id' => $this->vpc()->id,
        ]);

        $this->patch('/v2/volumes/' . $volume->id, [
            'volume_group_id' => $this->volumeGroup()->id
        ])->assertJsonFragment(
            [
                'title' => 'Validation Error',
                'detail' => 'Only shared volumes can be added to a volume group',
            ]
        )->assertStatus(422);
    }

    /**
     * I add a volume to a volume group - the volume is attached to an instance - an error is thrown
     */
    public function testAddToVolumeGroupVolumeAttachedToInstanceFails()
    {
        $volume = Volume::factory()->sharedVolume()->create([
            'vpc_id' => $this->vpc()->id,
        ]);

        $volume->instances()->attach($this->instanceModel());

        $this->patch('/v2/volumes/' . $volume->id, [
            'volume_group_id' => $this->volumeGroup()->id
        ])->assertJsonFragment(
            [
                'title' => 'Validation Error',
                'detail' => 'The volume is attached to one or more instances',
            ]
        )->assertStatus(422);
    }
}
