<?php
namespace Tests\unit\Jobs\Artisan\Host;

use App\Jobs\Artisan\Host\RemoveFrom3Par;
use App\Models\V2\Host;
use App\Models\V2\HostGroup;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class RemoveFrom3ParTest extends TestCase
{
    protected $job;
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
            $hostGroup = factory(HostGroup::class)->create([
                'id' => 'hg-test',
                'name' => 'hg-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'host_spec_id' => $this->hostSpec()->id,
            ]);
            return factory(Host::class)->create([
                'id' => 'h-test',
                'name' => 'h-test',
                'host_group_id' => $hostGroup->id,
            ]);
        });
        $this->job = \Mockery::mock(RemoveFrom3Par::class, [$this->host])->makePartial();
    }

    public function testRemoveWith404Error()
    {
        $this->artisanServiceMock()
            ->expects('get')
            ->withSomeOfArgs('/api/v2/san/MCS-E-G0-3PAR-01/host/h-test')
            ->andThrow(RequestException::create(new Request('DELETE', ''), new Response(404)));
        Log::shouldReceive('info')
            ->withSomeOfArgs(get_class($this->job) . ' : Started');
        Log::shouldReceive('warning')
            ->withSomeOfArgs(get_class($this->job) . ' : 3Par for Host h-test could not be retrieved, skipping.');
        $this->assertFalse($this->job->handle());
    }

    public function testRemoveWith500Error()
    {
        $this->artisanServiceMock()
            ->expects('get')
            ->withSomeOfArgs('/api/v2/san/MCS-E-G0-3PAR-01/host/h-test')
            ->andReturnUsing(function () {
                return new Response(200);
            });
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
            ->expects('get')
            ->withSomeOfArgs('/api/v2/san/MCS-E-G0-3PAR-01/host/h-test')
            ->andReturnUsing(function () {
                return new Response(200);
            });
        $this->artisanServiceMock()
            ->expects('delete')
            ->withSomeOfArgs('/api/v2/san/MCS-E-G0-3PAR-01/host/h-test')
            ->andReturnUsing(function () {
                return new Response(200);
            });

        $this->assertNull($this->job->handle());
    }
}