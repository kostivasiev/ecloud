<?php
namespace Tests\unit\Jobs\Kingpin\Host;

use App\Jobs\Kingpin\Host\MaintenanceMode;
use App\Models\V2\Host;
use App\Models\V2\HostGroup;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MaintenanceModeTest extends TestCase
{
    protected $job;
    protected Host $host;

    public function setUp(): void
    {
        parent::setUp();

        $hostGroup = HostGroup::factory()->create([
            'id' => 'hg-test',
            'name' => 'hg-test',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'host_spec_id' => $this->hostSpec()->id,
        ]);

        $this->host = Host::factory()->create([
            'id' => 'h-test',
            'name' => 'h-test',
            'host_group_id' => $hostGroup->id,
            'mac_address' => 'aa:bb:cc:dd:ee:ff',
        ]);
    }

    public function testSkipIfMacAddressNotSet()
    {
        $this->host->mac_address = '';
        $this->host->save();

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new MaintenanceMode($this->host));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testSkipIf404()
    {
        $this->kingpinServiceMock()
            ->expects('get')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/hostgroup/hg-test/host/aa:bb:cc:dd:ee:ff')
            ->andThrow(RequestException::create(new Request('GET', ''), new Response(404)));

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new MaintenanceMode($this->host));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testMaintenanceModeFail()
    {
        $this->kingpinServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/hostgroup/hg-test/host/aa:bb:cc:dd:ee:ff')
            ->andReturnUsing(function () {
                return new Response('200', [], json_encode([]));
            });
        $this->kingpinServiceMock()
            ->expects('post')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/hostgroup/hg-test/host/aa:bb:cc:dd:ee:ff/maintenance')
            ->andThrow(RequestException::create(new Request('GET', ''), new Response(404)));

        $this->expectException(RequestException::class);

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new MaintenanceMode($this->host));

        Event::assertDispatched(JobFailed::class);
    }

    public function testMaintenanceModeSuccess()
    {
        $this->kingpinServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/hostgroup/hg-test/host/aa:bb:cc:dd:ee:ff')
            ->andReturnUsing(function () {
                return new Response('200', [], json_encode([]));
            });
        $this->kingpinServiceMock()
            ->expects('post')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/hostgroup/hg-test/host/aa:bb:cc:dd:ee:ff/maintenance')
            ->andReturnUsing(function () {
                return new Response(200);
            });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new MaintenanceMode($this->host));

        Event::assertNotDispatched(JobFailed::class);
    }

}