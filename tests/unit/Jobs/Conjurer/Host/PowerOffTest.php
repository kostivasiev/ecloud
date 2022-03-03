<?php
namespace Tests\unit\Jobs\Conjurer\Host;

use App\Events\V2\Task\Created;
use App\Jobs\Conjurer\Host\PowerOff;
use App\Models\V2\Host;
use App\Models\V2\HostGroup;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PowerOffTest extends TestCase
{
    protected $job;
    protected Host $host;

    public function setUp(): void
    {
        parent::setUp();

        $this->host = Host::withoutEvents(function () {
            $hostGroup = HostGroup::factory()->create([
                'id' => 'hg-test',
                'name' => 'hg-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'host_spec_id' => $this->hostSpec()->id,
            ]);
            return Host::factory()->create([
                'id' => 'h-test',
                'name' => 'h-test',
                'host_group_id' => $hostGroup->id,
            ]);
        });
    }

    public function testPowerOff404NoError()
    {
        $this->conjurerServiceMock()
            ->expects('get')
            ->withSomeOfArgs('/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test')
            ->andThrow(RequestException::create(new Request('DELETE', ''), new Response(404)));

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new PowerOff($this->host));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testPowerOff500Error()
    {
        $this->conjurerServiceMock()
            ->expects('get')
            ->withSomeOfArgs('/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test')
            ->andReturnUsing(function () {
                return new Response(200);
            });
        $this->conjurerServiceMock()
            ->expects('delete')
            ->withSomeOfArgs('/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test/power')
            ->andThrow(RequestException::create(new Request('DELETE', ''), new Response(500)));

        $this->expectExceptionCode(500);

        Event::fake([JobFailed::class]);

        dispatch(new PowerOff($this->host));

        Event::assertDispatched(JobFailed::class);
    }

    public function testPowerOffSuccess()
    {
        $this->conjurerServiceMock()
            ->expects('get')
            ->withSomeOfArgs('/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test')
            ->andReturnUsing(function () {
                return new Response(200);
            });
        $this->conjurerServiceMock()
            ->expects('delete')
            ->withSomeOfArgs('/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test/power')
            ->andReturnUsing(function () {
                return new Response(200);
            });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new PowerOff($this->host));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }
}