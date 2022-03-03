<?php

namespace Tests\unit\Traits\V2;

use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SyncableTest extends TestCase
{
    public function testGetSyncAttributeReturnsLatestSyncData()
    {
        Event::fake(\App\Events\V2\Task\Created::class);

        $model = new SyncableTestModel([
            'id' => 'test-testing'
        ]);

        $task = new Task([
            'id' => 'task-test',
            'name' => Sync::TASK_NAME_UPDATE,
            'completed' => true,
        ]);
        $task->resource()->associate($model);
        $task->save();

        $attribute = $model->sync;

        $this->assertEquals(Sync::STATUS_COMPLETE, $attribute->status);
        $this->assertEquals(Sync::TYPE_UPDATE, $attribute->type);
    }

    public function testGetSyncAttributeReturnsUnknownWithNoSync()
    {
        Event::fake(\App\Events\V2\Task\Created::class);

        $model = new SyncableTestModel([
            'id' => 'test-testing'
        ]);

        $attribute = $model->sync;

        $this->assertEquals('complete', $attribute->status);
        $this->assertEquals('n/a', $attribute->type);
    }

    public function testSyncSave()
    {
        Event::fake(\App\Events\V2\Task\Created::class);

        $model = new SyncableTestModel([
            'id' => 'test-testing'
        ]);

        $task = $model->syncSave(['testKey' => 'testVal']);

        Event::assertDispatched(\App\Events\V2\Task\Created::class);

        $this->assertEquals('sync_update', $task->name);
        $this->assertEquals('App\Jobs\Sync\SyncableTestModel\Update', $task->job);
        $this->assertEquals(['testKey' => 'testVal'], $task->data);
    }

    public function testSyncDelete()
    {
        Event::fake(\App\Events\V2\Task\Created::class);

        $model = new SyncableTestModel([
            'id' => 'test-testing'
        ]);

        $task = $model->syncDelete(['testKey' => 'testVal']);

        Event::assertDispatched(\App\Events\V2\Task\Created::class);

        $this->assertEquals('sync_delete', $task->name);
        $this->assertEquals('App\Jobs\Sync\SyncableTestModel\Delete', $task->job);
        $this->assertEquals(['testKey' => 'testVal'], $task->data);
    }
}
