<?php

namespace Tests\unit\Traits\V2;

use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SyncableTest extends TestCase
{
    public $model;
    public $task;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testGetSyncAttributeReturnsLatestSyncData()
    {
        Model::withoutEvents(function () {
            $this->model = new TestModel([
                'id' => 'test-testing'
            ]);

            $this->task = new Task([
                'id' => 'task-test',
                'name' => Sync::TASK_NAME_UPDATE,
                'completed' => true,
            ]);
            $this->task->resource()->associate($this->model);
            $this->task->save();
        });

        $attribute = $this->model->sync;

        $this->assertEquals(Sync::STATUS_COMPLETE, $attribute->status);
        $this->assertEquals(Sync::TYPE_UPDATE, $attribute->type);
    }

    public function testGetSyncAttributeReturnsUnknownWithNoSync()
    {
        Model::withoutEvents(function () {
            $this->model = new TestModel([
                'id' => 'test-testing'
            ]);
        });

        $attribute = $this->model->sync;

        $this->assertEquals('complete', $attribute->status);
        $this->assertEquals('n/a', $attribute->type);
    }

    public function testSyncSave()
    {
        Event::fake(\App\Events\V2\Task\Created::class);

        $model = Model::withoutEvents(function() {
            return new TestModel([
                'id' => 'test-testing'
            ]);
        });

        $task = $model->syncSave(['testKey' => 'testVal']);

        Event::assertDispatched(\App\Events\V2\Task\Created::class);

        $this->assertEquals('sync_update', $task->name);
        $this->assertEquals('App\Jobs\Sync\TestModel\Update', $task->job);
        $this->assertEquals(['testKey' => 'testVal'], $task->data);
    }

    public function testSyncDelete()
    {
        Event::fake(\App\Events\V2\Task\Created::class);

        $model = Model::withoutEvents(function() {
            return new TestModel([
                'id' => 'test-testing'
            ]);
        });

        $task = $model->syncDelete(['testKey' => 'testVal']);

        Event::assertDispatched(\App\Events\V2\Task\Created::class);

        $this->assertEquals('sync_delete', $task->name);
        $this->assertEquals('App\Jobs\Sync\TestModel\Delete', $task->job);
        $this->assertEquals(['testKey' => 'testVal'], $task->data);
    }
}
