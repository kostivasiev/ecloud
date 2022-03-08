<?php

namespace Tests\V2\Volume;

use Tests\Mocks\Resources\VolumeGroupMock;
use Tests\Mocks\Resources\VolumeMock;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class HiddenInstanceVolumesTest extends TestCase
{
    use VolumeMock, VolumeGroupMock;

    protected Consumer $consumer;

    public function setUp(): void
    {
        parent::setUp();
        $this->consumer = new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']);
    }

    public function testVolumeAttachedToNormalInstanceVisible()
    {
        $this->be($this->consumer);
        $this->volume()->instances()->attach($this->instanceModel());
        $this->get('/v2/volumes')
            ->assertJsonFragment([
                'id' => $this->volume()->id,
            ])->assertStatus(200);
    }

    public function testVolumeAttachedToHiddenInstanceHidden()
    {
        $this->be($this->consumer);
        $this->instanceModel()->setAttribute('is_hidden', true)->saveQuietly();
        $this->volume()->instances()->attach($this->instanceModel());
        $this->get('/v2/volumes')
            ->assertJsonMissing([
                'id' => $this->volume()->id,
            ])->assertStatus(200);
    }

    public function testVpcVolumesEndpointShowsVisibleVolumes()
    {
        $this->be($this->consumer);
        $this->volume()->instances()->attach($this->instanceModel());
        $this->get('/v2/vpcs/' . $this->vpc()->id . '/volumes')
            ->assertJsonFragment([
                'id' => $this->volume()->id,
            ])->assertStatus(200);
    }

    public function testVpcVolumesEndpointHidesHiddenVolumes()
    {
        $this->be($this->consumer);
        $this->instanceModel()->setAttribute('is_hidden', true)->saveQuietly();
        $this->volume()->instances()->attach($this->instanceModel());
        $this->get('/v2/vpcs/' . $this->vpc()->id . '/volumes')
            ->assertJsonMissing([
                'id' => $this->volume()->id,
            ])->assertStatus(200);
    }

    public function testInstanceVolumesEndpointShowsVisibleVolumes()
    {
        $this->be($this->consumer);
        $this->volume()->instances()->attach($this->instanceModel());
        $this->get('/v2/instances/' . $this->instanceModel()->id . '/volumes')
            ->assertJsonFragment([
                'id' => $this->volume()->id,
            ])->assertStatus(200);
    }

    // hidden instances are not visible through this endpoint
    public function testInstanceVolumesEndpointHidesHiddenVolumes()
    {
        $this->be($this->consumer);
        $this->instanceModel()->setAttribute('is_hidden', true)->saveQuietly();
        $this->volume()->instances()->attach($this->instanceModel());
        $this->get('/v2/instances/' . $this->instanceModel()->id . '/volumes')
            ->assertStatus(404);
    }

    public function testVolumeVisibleInstance()
    {
        $this->be($this->consumer);
        $this->volume()->instances()->attach($this->instanceModel());
        $this->get('/v2/volumes/' . $this->volume()->id . '/instances')
            ->assertJsonFragment([
                'id' => $this->instanceModel()->id,
            ])->assertStatus(200);
    }

    public function testVolumeInstancesHidden()
    {
        $this->be($this->consumer);
        $this->instanceModel()->setAttribute('is_hidden', true)->saveQuietly();
        $this->volume()->instances()->attach($this->instanceModel());
        $this->get('/v2/volumes/' . $this->volume()->id . '/instances')
            ->assertStatus(404);
    }

    public function testVolumeVisibleTasks()
    {
        $this->be($this->consumer);
        $task = $this->createSyncUpdateTask($this->volume());
        $this->volume()->instances()->attach($this->instanceModel());
        $this->get('/v2/volumes/' . $this->volume()->id . '/tasks')
            ->assertJsonFragment([
                'id' => $task->id,
            ])->assertStatus(200);
    }

    public function testVolumeHiddenTasks()
    {
        $this->be($this->consumer);
        $task = $this->createSyncUpdateTask($this->volume());
        $this->instanceModel()->setAttribute('is_hidden', true)->saveQuietly();
        $this->volume()->instances()->attach($this->instanceModel());
        $this->get('/v2/volumes/' . $this->volume()->id . '/tasks')
            ->assertStatus(404);
    }

    public function testVolumeVisibleVolumeGroups()
    {
        $this->be($this->consumer);
        $this->volume()->instances()->attach($this->instanceModel());
        $this->volume()->setAttribute('volume_group_id', $this->volumeGroup()->id)->saveQuietly();
        $response = $this->get('/v2/volume-groups/' . $this->volumeGroup()->id . '/volumes')
            ->assertJsonFragment([
                'id' => $this->volume()->id,
            ])->assertStatus(200);
    }

    public function testVolumeHiddenVolumeGroups()
    {
        $this->be($this->consumer);
        $this->instanceModel()->setAttribute('is_hidden', true)->saveQuietly();
        $this->volume()->instances()->attach($this->instanceModel());
        $this->volume()->setAttribute('volume_group_id', $this->volumeGroup()->id)->saveQuietly();
        $this->get('/v2/volume-groups/' . $this->volumeGroup()->id . '/volumes')
            ->assertJsonMissing([
                'id' => $this->volume()->id,
            ])->assertStatus(200);
    }
}
