<?php

namespace Tests\Unit\Console\Commands\Nsx;

use App\Console\Commands\Nsx\UndeployOrphanedResources;
use App\Events\V2\Task\Created;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UndeployOrphanedResourcesTest extends TestCase
{
    public $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = \Mockery::mock(UndeployOrphanedResources::class)->makePartial();

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
        $this->vpc()->setAttribute('reseller_id', 7052)->saveQuietly();
    }

    public function testRoutersAndNetworksNotFoundOnNsxDoNotGetDeleted()
    {
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

        foreach ($this->command->markedForDeletion as [$id, $name, $reason, $exists]) {
            $this->assertEquals('No', $exists);
        }
    }

    public function testRouterHasNoParentResourceGetsDeleted()
    {
        Event::fake(Created::class);
        $task = $this->createSyncUpdateTask($this->router());

        $this->vpc()->delete();

        // Router is found on NSX
        $this->nsxServiceMock()->expects('get')
            ->withArgs(['policy/api/v1/infra/tier-1s/' . $this->router()->id])
            ->andReturnTrue();

        $task->setAttribute('completed', true)->saveQuietly();

        $this->command->handle();

        Event::assertDispatched(Created::class);
    }

    public function testRouterHasParentResourceDoesNotGetDeleted()
    {
        $this->createSyncUpdateTask($this->router());

        // Router is not found on NSX
        $this->nsxServiceMock()
            ->shouldNotReceive('get')
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
        $deleteActionsPerformed = 0;
        $this->createSyncUpdateTask($this->router());

        $this->router()->delete();

        // Network is found on NSX
        $this->nsxServiceMock()->expects('get')
            ->withArgs(['policy/api/v1/infra/tier-1s/' . $this->router()->id . '/segments/' . $this->network()->id])
            ->andReturnTrue();

        // Router is not found on NSX
        $this->nsxServiceMock()->shouldNotReceive('get')
            ->withArgs(['policy/api/v1/infra/tier-1s/' . $this->router()->id])
            ->andThrow(
                new ClientException('Not Found', new Request('GET', 'test'), new Response(404))
            );

        $this->nsxServiceMock()->expects('delete')
            ->withSomeOfArgs('policy/api/v1/infra/tier-1s/' . $this->router()->id . '/segments/' . $this->network()->id)
            ->andReturnUsing(function () use (&$deleteActionsPerformed) {
                $deleteActionsPerformed++;
                return new Response(200);
            });

        $this->command->handle();

        $this->assertEquals(1, $deleteActionsPerformed);
    }
}
