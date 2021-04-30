<?php

namespace Tests\unit\Rules\V2;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Nic;
use App\Models\V2\Task;
use App\Rules\V2\IpAvailable;
use App\Support\Sync;
use App\Traits\V2\DefaultAvailabilityZone;
use App\Traits\V2\Syncable;
use Faker\Factory as Faker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class TestTaskModel extends Model
{
    use Syncable;

    protected $fillable = [
        'id',
    ];
}

class TaskableTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testGetTaskAttributeReturnsLatestTaskData()
    {
        Model::withoutEvents(function() {
            $this->model = new TestTaskModel([
                'id' => 'test-testing'
            ]);

            $this->task = new Task([
                'id' => 'task-test',
                'name' => 'test',
                'completed' => true,
            ]);
            $this->task->resource()->associate($this->model);
            $this->task->save();
        });

        $attribute = $this->model->task;

        $this->assertEquals(Task::STATUS_COMPLETE, $attribute->status);
        $this->assertEquals('test', $attribute->name);
    }

    public function testGetTaskAttributeReturnsUnknownWithNoTask()
    {
        Model::withoutEvents(function() {
            $this->model = new TestTaskModel([
                'id' => 'test-testing'
            ]);
        });

        $attribute = $this->model->task;

        $this->assertEquals('unknown', $attribute->status);
        $this->assertEquals('unknown', $attribute->name);
    }
}
