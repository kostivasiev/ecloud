<?php
namespace Tests\unit\Jobs\Artisan\Host;

use App\Jobs\Artisan\Host\RemoveFrom3Par;
use App\Models\V2\Host;
use App\Models\V2\HostGroup;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RemoveFrom3ParTest extends TestCase
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

    public function testRemoveWith404Error()
    {
        $this->artisanServiceMock()
            ->expects('get')
            ->withSomeOfArgs('/api/v2/san/MCS-E-G0-3PAR-01/host/h-test')
            ->andThrow(RequestException::create(new Request('DELETE', ''), new Response(404)));

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new RemoveFrom3Par($this->host));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
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

        Event::fake([JobFailed::class]);

        dispatch(new RemoveFrom3Par($this->host));

        Event::assertDispatched(JobFailed::class);
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

        Event::fake([JobFailed::class]);

        dispatch(new RemoveFrom3Par($this->host));

        Event::assertNotDispatched(JobFailed::class);
    }
}