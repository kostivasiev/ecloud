<?php

namespace Tests\unit\Traits\V2;

use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
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

        $this->assertEquals('unknown', $attribute->status);
        $this->assertEquals('unknown', $attribute->type);
    }
}
