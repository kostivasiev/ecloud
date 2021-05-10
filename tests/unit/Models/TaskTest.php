<?php

namespace Tests\unit\Models;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\FloatingIp;
use App\Models\V2\Instance;
use App\Models\V2\Nat;
use App\Models\V2\Network;
use App\Models\V2\Nic;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Task;
use App\Models\V2\Vpc;
use App\Support\Sync;
use App\Traits\V2\Syncable;
use Faker\Factory as Faker;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class TestModel extends Model
{
    use Syncable;

    protected $fillable = [
        'id',
    ];
}

class TaskTest extends TestCase
{
    protected $task;
    
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testGetStatusAttributeReturnsFailedWhenFailed()
    {
        Model::withoutEvents(function() {
            $this->model = new TestModel([
                'id' => 'test-testing'
            ]);

            $this->task = new Task([
                'id' => 'task-test',
            ]);
            $this->task->resource()->associate($this->model);
            $this->task->completed = false;
            $this->task->failure_reason = 'some failure';
            $this->task->name = 'test';
            $this->task->save();
        });

        $status = $this->task->status;

        $this->assertEquals("failed", $status);
    }

    public function testGetStatusAttributeReturnsCompleteWhenComplete()
    {
        Model::withoutEvents(function() {
            $this->model = new TestModel([
                'id' => 'test-testing'
            ]);

            $this->task = new Task([
                'id' => 'task-test',
            ]);
            $this->task->resource()->associate($this->model);
            $this->task->completed = true;
            $this->task->name = 'test';
            $this->task->save();
        });

        $status = $this->task->status;

        $this->assertEquals("complete", $status);
    }

    public function testGetStatusAttributeReturnsInProgressWhenNotComplete()
    {
        Model::withoutEvents(function() {
            $this->model = new TestModel([
                'id' => 'test-testing'
            ]);

            $this->task = new Task([
                'id' => 'task-test',
            ]);
            $this->task->resource()->associate($this->model);
            $this->task->completed = false;
            $this->task->name = 'test';
            $this->task->save();
        });

        $status = $this->task->status;

        $this->assertEquals("in-progress", $status);
    }

}
