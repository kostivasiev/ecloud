<?php
namespace Tests\unit\Jobs\Conjurer\Host;

use App\Jobs\Conjurer\Host\PowerOff;
use App\Models\V2\Host;
use App\Models\V2\Sync;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class PowerOffTest extends TestCase
{
    use DatabaseMigrations;

    protected PowerOff $job;
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
        $this->job = new PowerOff($this->host);
    }

    public function testPowerOff404Error()
    {
        $this->conjurerServiceMock()
            ->expects('delete')
            ->withSomeOfArgs('/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test/power')
            ->andThrow(RequestException::create(new Request('DELETE', ''), new Response(404)));
        Log::shouldReceive('info')
            ->withSomeOfArgs(PowerOff::class . ' : Started');
        Log::shouldReceive('error')
            ->withSomeOfArgs(PowerOff::class . ' : Failed');
        Log::shouldReceive('warning')
            ->withSomeOfArgs(PowerOff::class . ' : Host h-test was not powered off.');

        $this->assertNull($this->job->handle());
    }

    public function testPowerOff500Error()
    {
        $this->conjurerServiceMock()
            ->expects('delete')
            ->withSomeOfArgs('/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test/power')
            ->andThrow(RequestException::create(new Request('DELETE', ''), new Response(500)));

        $this->expectException(ServerException::class);
        $this->expectExceptionCode(500);
        $this->expectExceptionMessage('Server error: `DELETE ` resulted in a `500 Internal Server Error` response');

        $this->job->handle();
    }

    public function testPowerOffSuccess()
    {
        $this->conjurerServiceMock()
            ->expects('delete')
            ->withSomeOfArgs('/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test/power')
            ->andReturnUsing(function () {
                return new Response(200);
            });

        $this->assertNull($this->job->handle());
    }
}