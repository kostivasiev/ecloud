<?php

namespace Tests\Unit\Jobs\Instance;

use App\Jobs\Instance\AssociateHostGroup;
use App\Models\V2\HostGroup;
use App\Models\V2\Task;
use App\Tasks\Instance\Migrate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AssociateHostGroupTest extends TestCase
{
    protected Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        Model::withoutEvents(function () {
            return HostGroup::factory()->create([
                'id' => 'hg-original',
                'name' => 'hg-original',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'host_spec_id' => $this->hostSpec()->id,
                'windows_enabled' => true,
            ]);
        });

        $this->instanceModel()->setAttribute('host_group_id', 'hg-original')->saveQuietly();

        Task::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'test-task',
                'name' => Migrate::$name,
                'job' => Migrate::class,
                'data' => [
                    'host_group_id' => $this->hostGroup()->id,
                ]
            ]);
            $this->task->resource()->associate($this->instanceModel());
            $this->task->save();
        });
    }

    public function testAssociateSuccess()
    {
        Event::fake([JobFailed::class]);

        $this->assertEquals('hg-original', $this->instanceModel()->host_group_id);

        dispatch(new AssociateHostGroup($this->task));

        $this->instanceModel()->refresh();

        $this->assertEquals($this->hostGroup()->id, $this->instanceModel()->host_group_id);

        Event::assertNotDispatched(JobFailed::class);
    }
}
