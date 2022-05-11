<?php

namespace Tests\Unit\Console\Commands\VPC;

use App\Console\Commands\VPC\DeleteOrphanedResources;
use App\Models\V2\Task;
use App\Support\Sync;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class DeleteOrphanedResourcesTest extends TestCase
{
    public $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = \Mockery::mock(DeleteOrphanedResources::class)->makePartial();

        // Not test-run
        $this->command->allows('option')
            ->withAnyArgs()
            ->andReturnFalse();

        // Confirm delete records
        $this->command->allows('confirm')
            ->withAnyArgs()
            ->andReturnTrue();

        $this->command->allows('info')->andReturnFalse();

        // Tabular output
        $this->command->allows('table')
            ->withAnyArgs()
            ->andReturnTrue();
        $this->command->allows('line')
            ->withAnyArgs()
            ->andReturnTrue();
    }

    public function testDeletesRoutersAndNetworksWithNoTasksAndNotFoundOnNsx()
    {
        $this->network();

        // Router is not found on NSX
        $this->nsxServiceMock()->expects('get')
            ->withArgs(['policy/api/v1/infra/tier-1s/' . $this->router()->id])
            ->andThrow(
                new ClientException('Not Found', new Request('GET', 'test'), new Response(404))
            );

        // Network is not found on NSX
        $this->nsxServiceMock()->expects('get')
            ->withArgs(['policy/api/v1/infra/tier-1s/' . $this->router()->id . '/segments/' . $this->network()->id])
            ->andThrow(
                new ClientException('Not Found', new Request('GET', 'test'), new Response(404))
            );

        $this->command->handle();

        $this->router()->refresh();

        $this->assertSoftDeleted($this->router());

        $this->assertSoftDeleted($this->network());
    }

    public function testRoutersAndNetworksFoundOnNsxDoNotGetDeleted()
    {
        $this->network();

        // Router is found on NSX
        $this->nsxServiceMock()->expects('get')
            ->withArgs(['policy/api/v1/infra/tier-1s/' . $this->router()->id])
            ->andReturnTrue();

        // Network is found on NSX
        $this->nsxServiceMock()->expects('get')
            ->withArgs(['policy/api/v1/infra/tier-1s/' . $this->router()->id . '/segments/' . $this->network()->id])
            ->andReturnTrue();

        // Confirm delete records
        $this->command->allows('confirm')
            ->withAnyArgs()
            ->andReturnTrue();

        // Tabular output
        $this->command->allows('table')
            ->withAnyArgs()
            ->andReturnTrue();
        $this->command->allows('line')
            ->withAnyArgs()
            ->andReturnTrue();

        $this->command->handle();

        $this->router()->refresh();

        $this->assertNotSoftDeleted($this->router());

        $this->assertNotSoftDeleted($this->network());
    }

    public function testRouterHasNoParentResourceGetsDeleted()
    {
        $this->createSyncUpdateTask($this->router());

        $this->vpc()->delete();

        // Router is not found on NSX
        $this->nsxServiceMock()->expects('get')
            ->withArgs(['policy/api/v1/infra/tier-1s/' . $this->router()->id])
            ->andThrow(
                new ClientException('Not Found', new Request('GET', 'test'), new Response(404))
            );

        $this->command->handle();

        $this->router()->refresh();

        $this->assertSoftDeleted($this->router());
    }

    public function testRouterHasParentResourceDoesNotGetDeleted()
    {
        $this->createSyncUpdateTask($this->router());

        // Router is not found on NSX
        $this->nsxServiceMock()->shouldNotReceive('get')
            ->withArgs(['policy/api/v1/infra/tier-1s/' . $this->router()->id])
            ->andThrow(
                new ClientException('Not Found', new Request('GET', 'test'), new Response(404))
            );

        $this->command->handle();

        $this->router()->refresh();

        $this->assertNotSoftDeleted($this->router());
    }

    public function testNetworkHasNoParentResourceGetsDeleted()
    {
        $this->createSyncUpdateTask($this->router());

        $this->router()->delete();

        // Network is not found on NSX
        $this->nsxServiceMock()->expects('get')
            ->withArgs(['policy/api/v1/infra/tier-1s/' . $this->router()->id . '/segments/' . $this->network()->id])
            ->andThrow(
                new ClientException('Not Found', new Request('GET', 'test'), new Response(404))
            );

        // Router is not found on NSX
        $this->nsxServiceMock()->shouldNotReceive('get')
            ->withArgs(['policy/api/v1/infra/tier-1s/' . $this->router()->id])
            ->andThrow(
                new ClientException('Not Found', new Request('GET', 'test'), new Response(404))
            );

        $this->command->handle();

        $this->network()->refresh();

        $this->assertSoftDeleted($this->network());
    }

    public function testNetworkHasParentResourceDoesNotGetDeleted()
    {
        $this->createSyncUpdateTask($this->router());
        Task::withoutEvents(function () {
            $task = new Task([
                'id' => 'sync-2',
                'name' => Sync::TASK_NAME_UPDATE,
                'data' => null
            ]);
            $task->resource()->associate($this->network());
            $task->save();
            return $task;
        });

        // Router is not found on NSX
        $this->nsxServiceMock()->shouldNotReceive('get')
            ->withArgs(['policy/api/v1/infra/tier-1s/' . $this->router()->id . '/segments/' . $this->network()->id])
            ->andThrow(
                new ClientException('Not Found', new Request('GET', 'test'), new Response(404))
            );

        // Router is not found on NSX
        $this->nsxServiceMock()->shouldNotReceive('get')
            ->withArgs(['policy/api/v1/infra/tier-1s/' . $this->router()->id])
            ->andThrow(
                new ClientException('Not Found', new Request('GET', 'test'), new Response(404))
            );

        $this->command->handle();

        $this->network()->refresh();

        $this->assertNotSoftDeleted($this->network());
    }
}
