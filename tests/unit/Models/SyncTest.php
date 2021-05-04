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
use App\Models\V2\Sync;
use App\Models\V2\Vpc;
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

class SyncTest extends TestCase
{
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

            $this->sync = new Sync([
                'id' => 'sync-test',
            ]);
            $this->sync->resource()->associate($this->model);
            $this->sync->completed = false;
            $this->sync->failure_reason = 'some failure';
            $this->sync->type = Sync::TYPE_UPDATE;
            $this->sync->save();
        });

        $status = $this->sync->status;

        $this->assertEquals("failed", $status);
    }

    public function testGetStatusAttributeReturnsCompleteWhenComplete()
    {
        Model::withoutEvents(function() {
            $this->model = new TestModel([
                'id' => 'test-testing'
            ]);

            $this->sync = new Sync([
                'id' => 'sync-test',
            ]);
            $this->sync->resource()->associate($this->model);
            $this->sync->completed = true;
            $this->sync->type = Sync::TYPE_UPDATE;
            $this->sync->save();
        });

        $status = $this->sync->status;

        $this->assertEquals("complete", $status);
    }

    public function testGetStatusAttributeReturnsInProgressWhenNotComplete()
    {
        Model::withoutEvents(function() {
            $this->model = new TestModel([
                'id' => 'test-testing'
            ]);

            $this->sync = new Sync([
                'id' => 'sync-test',
            ]);
            $this->sync->resource()->associate($this->model);
            $this->sync->completed = false;
            $this->sync->type = Sync::TYPE_UPDATE;
            $this->sync->save();
        });

        $status = $this->sync->status;

        $this->assertEquals("in-progress", $status);
    }

}
