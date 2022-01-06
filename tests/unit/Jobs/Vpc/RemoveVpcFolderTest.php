<?php
namespace Tests\unit\Jobs\Vpc;

use App\Jobs\Vpc\RemoveVPCFolder;
use App\Models\V2\Region;
use App\Models\V2\Task;
use App\Models\V2\Vpc;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RemoveVpcFolderTest extends TestCase
{
    protected Task $task;

    public function setUp(): void
    {
        parent::setUp();

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->vpc());
            $this->task->save();
        });
    }

    public function testRemoveVPCJobIsDispatched()
    {
        $this->kingpinServiceMock()
            ->expects('delete')
            ->once()
            ->withSomeOfArgs('/api/v2/vpc/' . $this->vpc()->id);

        Event::fake([JobFailed::class, JobProcessed::class]);
        dispatch(new RemoveVPCFolder($this->task));
        Event::assertNotDispatched(JobFailed::class);
    }
}
