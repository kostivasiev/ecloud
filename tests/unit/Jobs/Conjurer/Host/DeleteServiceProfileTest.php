<?php
namespace Tests\unit\Jobs\Conjurer\Host;

use App\Jobs\Conjurer\Host\DeleteServiceProfile;
use App\Models\V2\Host;
use App\Models\V2\HostGroup;
use App\Models\V2\Sync;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
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
        $this->job = \Mockery::mock(DeleteServiceProfile::class, [$this->host])->makePartial();
    }

    public function testDeleteHostDoesNotExist()
    {
        $this->conjurerServiceMock()
            ->expects('get')
            ->withSomeOfArgs('/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test')
            ->andThrow(RequestException::create(new Request('DELETE', ''), new Response(404)));
        Log::shouldReceive('info')
            ->withSomeOfArgs(get_class($this->job) . ' : Started');
        Log::shouldReceive('warning')
            ->withSomeOfArgs(get_class($this->job) . ' : Service Profile for Host h-test not found, skipping.');
        $this->assertFalse($this->job->handle());
    }

    public function testDeleteHost500Error()
    {
        $this->conjurerServiceMock()
            ->expects('get')
            ->withSomeOfArgs('/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test')
            ->andReturnUsing(function () {
                return new Response(200);
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
        $this->conjurerServiceMock()
            ->expects('get')
            ->withSomeOfArgs('/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test')
            ->andReturnUsing(function () {
                return new Response(200);
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