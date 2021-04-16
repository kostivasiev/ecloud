<?php
namespace Tests\unit\Jobs\Conjurer\Host;

use App\Jobs\Conjurer\Host\DeleteServiceProfile;
use App\Models\V2\Host;
use App\Models\V2\Sync;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteServiceProfileTest extends TestCase
{
    use DatabaseMigrations;

    protected Host $host;
    protected $job;

    public function setUp(): void
    {
        parent::setUp();
        app()->bind(Sync::class, function () {
            return new Sync([
                'id' => 'sync-test',
            ]);
        });
        $this->host = Host::withoutEvents(function () {
            return factory(Host::class)->create([
                'id' => 'h-test',
                'name' => 'h-test',
                'host_group_id' => $this->hostGroup()->id,
            ]);
        });
        $this->job = \Mockery::mock(DeleteServiceProfile::class, [$this->host])->makePartial();
    }

    public function testSkipIfCancelled()
    {
        $this->job->expects('batch')
            ->andReturnUsing(function () {
                $batchMock = \Mockery::mock(Batch::class)->makePartial();
                $batchMock->expects('cancelled')->andReturnTrue();
                return $batchMock;
            });
        $this->assertNull($this->job->handle());
    }

    public function testDeleteHostDoesNotExist()
    {
        $this->job->expects('batch')
            ->andReturnUsing(function () {
                $batchMock = \Mockery::mock(Batch::class)->makePartial();
                $batchMock->expects('cancelled')->andReturnFalse();
                return $batchMock;
            });
        $this->conjurerServiceMock()
            ->expects('delete')
            ->withSomeOfArgs('/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test')
            ->andThrow(RequestException::create(new Request('DELETE', ''), new Response(404)));
        Log::shouldReceive('info')
            ->withSomeOfArgs(get_class($this->job) . ' : Started');
        Log::shouldReceive('error')
            ->withSomeOfArgs(get_class($this->job) . ' : Failed');
        Log::shouldReceive('warning')
            ->withSomeOfArgs(get_class($this->job) . ' : Service Profile for Host h-test was not found.');

        $this->assertNull($this->job->handle());
    }

    public function testDeleteHost500Error()
    {
        $this->job->expects('batch')
            ->andReturnUsing(function () {
                $batchMock = \Mockery::mock(Batch::class)->makePartial();
                $batchMock->expects('cancelled')->andReturnFalse();
                return $batchMock;
            });
        $this->conjurerServiceMock()
            ->expects('delete')
            ->withSomeOfArgs('/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test')
            ->andThrow(RequestException::create(new Request('DELETE', ''), new Response(500)));

        $this->expectException(ServerException::class);
        $this->expectExceptionCode(500);
        $this->expectExceptionMessage('500 Internal Server Error');

        $this->job->handle();
    }

    public function testDeleteHostSuccess()
    {
        $this->job->expects('batch')
            ->andReturnUsing(function () {
                $batchMock = \Mockery::mock(Batch::class)->makePartial();
                $batchMock->expects('cancelled')->andReturnFalse();
                return $batchMock;
            });
        $this->conjurerServiceMock()
            ->expects('delete')
            ->withSomeOfArgs('/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test')
            ->andReturnUsing(function () {
                return new Response(204);
            });
        $this->assertNull($this->job->handle());
    }
}