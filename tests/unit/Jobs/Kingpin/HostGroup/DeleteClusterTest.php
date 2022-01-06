<?php
namespace Tests\unit\Jobs\Kingpin\HostGroup;

use App\Jobs\Kingpin\HostGroup\CreateCluster;
use App\Jobs\Kingpin\HostGroup\DeleteCluster;
use App\Models\V2\HostGroup;
use App\Models\V2\Task;
use App\Support\Sync;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteClusterTest extends TestCase
{
    protected $hostGroup;
    protected Task $task;

    public function setUp(): void
    {
        parent::setUp();

        Model::withoutEvents(function () {
            $this->hostGroup = factory(HostGroup::class)->create([
                'id' => 'hg-test',
                'name' => 'hg-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'host_spec_id' => $this->hostSpec()->id,
                'windows_enabled' => true,
            ]);

            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_DELETE,
            ]);
            $this->task->resource()->associate($this->hostGroup);
            $this->task->save();
        });
    }

    /**
     * @test Delete cluster fails after a 404 response is received. Logging should reflect this, but the job should
     * continue, skipping the failed component. This means that other tasks (including Database deletion) continue
     * uninterrupted.
     */
    public function testDeleteClusterFails404()
    {
        $this->kingpinServiceMock()->expects('delete')
            ->withSomeOfArgs('/api/v2/vpc/' . $this->hostGroup->vpc->id . '/hostgroup/' . $this->hostGroup->id)
            ->andThrow(new RequestException('Not Found', new Request('delete', '', []), new Response(404)));

        Event::fake([JobFailed::class]);

        dispatch(new DeleteCluster($this->task));

        Event::assertNotDispatched(JobFailed::class);
    }

    /**
     * @test Server exceptions should make the job fail
     */
    public function testDeleteClusterFails500()
    {
        $message = 'Server Error';
        $code = 500;
        $this->kingpinServiceMock()->expects('delete')
            ->withSomeOfArgs('/api/v2/vpc/' . $this->hostGroup->vpc->id . '/hostgroup/' . $this->hostGroup->id)
            ->andThrow(new ServerException($message, new Request('delete', '', []), new Response($code)));

        Event::fake([JobFailed::class, JobProcessed::class]);
        $this->expectExceptionMessage($message);
        $this->expectExceptionCode($code);

        dispatch(new DeleteCluster($this->task));
    }

    /**
     * @test Delete Cluster is successful after a 200 response received
     */
    public function testDeleteClusterSuccess()
    {
        $this->kingpinServiceMock()->expects('delete')
            ->withSomeOfArgs('/api/v2/vpc/' . $this->hostGroup->vpc->id . '/hostgroup/' . $this->hostGroup->id)
            ->andReturnUsing(function () {
                return new Response(200);
            });

        Event::fake([JobFailed::class]);

        dispatch(new DeleteCluster($this->task));

        Event::assertNotDispatched(JobFailed::class);
    }
}