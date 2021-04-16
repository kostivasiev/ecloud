<?php
namespace Tests\unit\Jobs\Kingpin\Host;

use App\Jobs\Kingpin\Host\CheckExists;
use App\Models\V2\Host;
use App\Models\V2\Sync;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CheckExistsTest extends TestCase
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
        $this->job = \Mockery::mock(CheckExists::class, [$this->host])->makePartial();
    }

    public function testCheckExistsFail()
    {
        $this->kingpinServiceMock()
            ->expects('get')
            ->withSomeOfArgs('/api/v2/san/MCS-E-G0-3PAR-01/host/h-test')
            ->andThrow(RequestException::create(new Request('GET', ''), new Response(404)));

        $this->job->expects('batch')
            ->andReturnUsing(function () {
                $batchMock = \Mockery::mock(Batch::class)->makePartial();
                $batchMock->expects('cancel')->andReturnTrue();
                return $batchMock;
            });

        Log::shouldReceive('info')
            ->withSomeOfArgs(get_class($this->job) . ' : Started');
        Log::shouldReceive('error')
            ->withSomeOfArgs(get_class($this->job) . ' : Failed');
        Log::shouldReceive('warning')
            ->withSomeOfArgs(get_class($this->job) . ' : Host does not exist, skipping.');

        $this->assertNull($this->job->handle());
    }

    public function testCheckExistsPasses()
    {
        $this->kingpinServiceMock()
            ->expects('get')
            ->withSomeOfArgs('/api/v2/san/MCS-E-G0-3PAR-01/host/h-test')
            ->andReturnUsing(function () {
                return new Response(200);
            });

        $this->assertNull($this->job->handle());
    }
}