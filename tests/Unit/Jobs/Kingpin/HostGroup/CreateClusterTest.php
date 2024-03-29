<?php

namespace Tests\Unit\Jobs\Kingpin\HostGroup;

use App\Jobs\Kingpin\HostGroup\CreateCluster;
use App\Models\V2\HostGroup;
use App\Models\V2\Task;
use App\Support\Sync;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateClusterTest extends TestCase
{
    protected $hostGroup;
    protected Task $task;

    public function setUp(): void
    {
        parent::setUp();

        Model::withoutEvents(function () {
            $this->hostGroup = HostGroup::factory()->create([
                'id' => 'hg-test',
                'name' => 'hg-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'host_spec_id' => $this->hostSpec()->id,
                'windows_enabled' => true,
            ]);

            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->hostGroup);
            $this->task->save();
        });
    }

    public function testHostGroupExists()
    {
        $this->kingpinServiceMock()
            ->expects('get')
            ->withSomeOfArgs('/api/v2/vpc/' . $this->hostGroup->vpc->id . '/hostgroup/' . $this->hostGroup->id)
            ->andReturnUsing(function () {
                return new Response(200);
            });

        Event::fake([JobFailed::class]);

        dispatch(new CreateCluster($this->task));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testCreateSuccessful()
    {
        $this->kingpinServiceMock()
            ->expects('get')
            ->withSomeOfArgs('/api/v2/vpc/' . $this->hostGroup->vpc->id . '/hostgroup/' . $this->hostGroup->id)
            ->andThrow(RequestException::create(new Request('GET', ''), new Response(404)));
        $this->kingpinServiceMock()
            ->expects('post')
            ->withSomeOfArgs('/api/v2/vpc/' . $this->hostGroup->vpc->id . '/hostgroup')
            ->andReturnUsing(function () {
                return new Response(201);
            });

        Event::fake([JobFailed::class]);

        dispatch(new CreateCluster($this->task));

        Event::assertNotDispatched(JobFailed::class);
    }
}