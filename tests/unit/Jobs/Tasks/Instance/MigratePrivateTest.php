<?php

namespace Tests\unit\Jobs\Tasks\Instance;

use App\Jobs\Tasks\Instance\MigratePrivate;
use App\Models\V2\HostGroup;
use App\Models\V2\HostSpec;
use App\Models\V2\Task;
use Illuminate\Bus\PendingBatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class MigratePrivateTest extends TestCase
{

    private $task;

    public function setUp(): void
    {
        parent::setUp();
        Model::withoutEvents(function() {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => 'test',
                'data' => [
                    'host_group_id' => $this->hostGroup()->id,
                ]
            ]);
            $this->task->resource()->associate($this->instance());
        });
    }

    public function testJobsBatchedPublicToPrivate()
    {
        Bus::fake();
        $job = new MigratePrivate($this->task);
        $job->handle();

        Bus::assertBatched(function (PendingBatch $batch) {
            return $batch->jobs->count() == 1 && count($batch->jobs->all()[0]) == 1;
        });
    }

    public function testJobsBatchedPrivateToPrivateSameSpec()
    {
        $this->instance()->hostGroup()->associate($this->hostGroup());
        $this->instance()->saveQuietly();

        $hostGroup = Model::withoutEvents(function () {
            return factory(HostGroup::class)->create([
                'id' => 'hg-2',
                'name' => 'hg-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'host_spec_id' => $this->hostSpec()->id,
                'windows_enabled' => true,
            ]);
        });

        $task = Model::withoutEvents(function() use ($hostGroup) {
            $task = new Task([
                'id' => 'sync-1',
                'name' => 'test',
                'data' => [
                    'host_group_id' => $hostGroup->id,
                ]
            ]);
            $task->resource()->associate($this->instance());
            return $task;
        });

        Bus::fake();
        $job = new MigratePrivate($task);
        $job->handle();

        Bus::assertBatched(function (PendingBatch $batch) {
            return $batch->jobs->count() == 1 && count($batch->jobs->all()[0]) == 1;
        });
    }

    public function testJobsBatchedPrivateToPrivateDifferentSpec()
    {
        $this->instance()->hostGroup()->associate($this->hostGroup());
        $this->instance()->saveQuietly();

        $hostSpec = factory(HostSpec::class)->create([
            'id' => 'hs-test2',
            'name' => 'test-host-spec',
        ]);

        $hostGroup = Model::withoutEvents(function () use($hostSpec) {
            return factory(HostGroup::class)->create([
                'id' => 'hg-2',
                'name' => 'hg-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'host_spec_id' => $hostSpec->id,
                'windows_enabled' => true,
            ]);
        });

        $task = Model::withoutEvents(function() use ($hostGroup) {
            $task = new Task([
                'id' => 'sync-1',
                'name' => 'test',
                'data' => [
                    'host_group_id' => $hostGroup->id,
                ]
            ]);
            $task->resource()->associate($this->instance());
            return $task;
        });

        Bus::fake();
        $job = new MigratePrivate($task);
        $job->handle();

        Bus::assertBatched(function (PendingBatch $batch) {
            return $batch->jobs->count() == 1 && count($batch->jobs->all()[0]) == 1;
        });
    }
}
