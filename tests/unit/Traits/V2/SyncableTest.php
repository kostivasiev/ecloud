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

class TestModel extends Model
{
    use Syncable;

    protected $fillable = [
        'id',
    ];
}

class SyncableTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testGetSyncAttributeReturnsLatestSyncData()
    {
        Model::withoutEvents(function() {
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
        Model::withoutEvents(function() {
            $this->model = new TestModel([
                'id' => 'test-testing'
            ]);
        });

        $attribute = $this->model->sync;

        $this->assertEquals('unknown', $attribute->status);
        $this->assertEquals('unknown', $attribute->type);
    }
}
