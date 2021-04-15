<?php
namespace Tests\unit\Jobs\Artisan\Host;

use App\Jobs\Artisan\Host\RemoveFrom3Par;
use App\Models\V2\Host;
use App\Models\V2\Sync;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class RemoveFrom3ParTest extends TestCase
{
    use DatabaseMigrations;

    protected RemoveFrom3Par $job;
    protected Host $host;

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
        $this->job = new RemoveFrom3Par($this->host);
    }

    public function testRemoveWith404Error()
    {
        $this->artisanServiceMock()
            ->expects('delete')
            ->withSomeOfArgs('/api/v2/san/MCS-E-G0-3PAR-01/host/h-test')
            ->andThrow(RequestException::create(new Request('DELETE', ''), new Response(404)));
        Log::shouldReceive('info')
            ->withSomeOfArgs(RemoveFrom3Par::class . ' : Started');
        Log::shouldReceive('error')
            ->withSomeOfArgs(RemoveFrom3Par::class . ' : Failed');
        Log::shouldReceive('warning')
            ->withSomeOfArgs(RemoveFrom3Par::class . ' : Host h-test was not removed from 3Par.');

        $this->assertNull($this->job->handle());
    }

    public function testRemoveWith500Error()
    {
        $this->artisanServiceMock()
            ->expects('delete')
            ->withSomeOfArgs('/api/v2/san/MCS-E-G0-3PAR-01/host/h-test')
            ->andThrow(RequestException::create(new Request('DELETE', ''), new Response(500)));

        $this->expectException(ServerException::class);
        $this->expectExceptionCode(500);
        $this->expectExceptionMessage('Server error: `DELETE ` resulted in a `500 Internal Server Error` response');

        $this->job->handle();
    }

    public function testRemoveSuccess()
    {
        $this->artisanServiceMock()
            ->expects('delete')
            ->withSomeOfArgs('/api/v2/san/MCS-E-G0-3PAR-01/host/h-test')
            ->andReturnUsing(function () {
                return new Response(200);
            });

        $this->assertNull($this->job->handle());
    }
}