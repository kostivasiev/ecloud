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
            ->seeJson([
                'id' => $this->volume()->id,
            ])->assertResponseOK();
    }

    public function testVolumeAttachedToHiddenInstanceHidden()
    {
        $this->be($this->consumer);
        $this->instanceModel()->setAttribute('is_hidden', true)->saveQuietly();
        $this->volume()->instances()->attach($this->instanceModel());
        $this->get('/v2/volumes')
            ->dontSeeJson([
                'id' => $this->volume()->id,
            ])->assertResponseOK();
    }

    public function testVpcVolumesEndpointShowsVisibleVolumes()
    {
        $this->be($this->consumer);
        $this->volume()->instances()->attach($this->instanceModel());
        $this->get('/v2/vpcs/' . $this->vpc()->id . '/volumes')
            ->seeJson([
                'id' => $this->volume()->id,
            ])->assertResponseOK();
    }

    public function testVpcVolumesEndpointHidesHiddenVolumes()
    {
        $this->be($this->consumer);
        $this->instanceModel()->setAttribute('is_hidden', true)->saveQuietly();
        $this->volume()->instances()->attach($this->instanceModel());
        $this->get('/v2/vpcs/' . $this->vpc()->id . '/volumes')
            ->dontSeeJson([
                'id' => $this->volume()->id,
            ])->assertResponseOK();
    }

    public function testInstanceVolumesEndpointShowsVisibleVolumes()
    {
        $this->be($this->consumer);
        $this->volume()->instances()->attach($this->instanceModel());
        $this->get('/v2/instances/' . $this->instanceModel()->id . '/volumes')
            ->seeJson([
                'id' => $this->volume()->id,
            ])->assertResponseOK();
    }

    // hidden instances are not visible through this endpoint
    public function testInstanceVolumesEndpointHidesHiddenVolumes()
    {
        $this->be($this->consumer);
        $this->instanceModel()->setAttribute('is_hidden', true)->saveQuietly();
        $this->volume()->instances()->attach($this->instanceModel());
        $this->get('/v2/instances/' . $this->instanceModel()->id . '/volumes')
            ->assertResponseStatus(404);
    }

    public function testVolumeVisibleInstance()
    {
        $this->be($this->consumer);
        $this->volume()->instances()->attach($this->instanceModel());
        $this->get('/v2/volumes/' . $this->volume()->id . '/instances')
            ->seeJson([
                'id' => $this->instanceModel()->id,
            ])->assertResponseOk();
    }

    public function testVolumeInstancesHidden()
    {
        $this->be($this->consumer);
        $this->instanceModel()->setAttribute('is_hidden', true)->saveQuietly();
        $this->volume()->instances()->attach($this->instanceModel());
        $this->get('/v2/volumes/' . $this->volume()->id . '/instances')
            ->assertResponseStatus(404);
    }

    public function testVolumeVisibleTasks()
    {
        $this->be($this->consumer);
        $task = $this->createSyncUpdateTask($this->volume());
        $this->volume()->instances()->attach($this->instanceModel());
        $this->get('/v2/volumes/' . $this->volume()->id . '/tasks')
            ->seeJson([
                'id' => $task->id,
            ])->assertResponseOk();
    }

    public function testVolumeHiddenTasks()
    {
        $this->be($this->consumer);
        $task = $this->createSyncUpdateTask($this->volume());
        $this->instanceModel()->setAttribute('is_hidden', true)->saveQuietly();
        $this->volume()->instances()->attach($this->instanceModel());
        $this->get('/v2/volumes/' . $this->volume()->id . '/tasks')
            ->assertResponseStatus(404);
    }

    public function testVolumeVisibleVolumeGroups()
    {
        $this->be($this->consumer);
        $this->volume()->instances()->attach($this->instanceModel());
        $this->volume()->setAttribute('volume_group_id', $this->volumeGroup()->id)->saveQuietly();
        $this->get('/v2/volume-groups/' . $this->volumeGroup()->id . '/volumes')
            ->seeJson([
                'id' => $this->volume()->id,
            ])->assertResponseOk();
    }

    public function testVolumeHiddenVolumeGroups()
    {
        $this->be($this->consumer);
        $this->instanceModel()->setAttribute('is_hidden', true)->saveQuietly();
        $this->volume()->instances()->attach($this->instanceModel());
        $this->volume()->setAttribute('volume_group_id', $this->volumeGroup()->id)->saveQuietly();
        $this->get('/v2/volume-groups/' . $this->volumeGroup()->id . '/volumes')
            ->dontSeeJson([
                'id' => $this->volume()->id,
            ])->assertResponseOk();
    }
}
