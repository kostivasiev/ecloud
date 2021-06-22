<?php
namespace Tests\unit\Jobs\Kingpin\Host;

use App\Jobs\Kingpin\Host\DeleteInVmware;
use App\Jobs\Kingpin\Host\MaintenanceMode;
use App\Models\V2\Host;
use App\Models\V2\HostGroup;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteInVmwareTest extends TestCase
{
    protected Host $host;
    protected $job;

    public function setUp(): void
    {
        parent::setUp();

        $hostGroup = factory(HostGroup::class)->create([
            'id' => 'hg-test',
            'name' => 'hg-test',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'host_spec_id' => $this->hostSpec()->id,
        ]);

        $this->host = factory(Host::class)->create([
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

        dispatch(new DeleteInVmware($this->host));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testHostNotFoundSkips()
    {
        $this->kingpinServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/hostgroup/hg-test/host/aa:bb:cc:dd:ee:ff')
            ->andThrow(RequestException::create(new Request('GET', ''), new Response(404)));

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new DeleteInVmware($this->host));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testUnableToDelete()
    {
        $this->kingpinServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/hostgroup/hg-test/host/aa:bb:cc:dd:ee:ff')
            ->andReturnUsing(function () {
                return new Response('200', [], json_encode([]));
            });
        $this->kingpinServiceMock()
            ->expects('delete')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/hostgroup/hg-test/host/aa:bb:cc:dd:ee:ff')
            ->andThrow(RequestException::create(new Request('DELETE', ''), new Response(500)));

        Event::fake([JobFailed::class]);

        $this->expectException(RequestException::class);

        dispatch(new DeleteInVmware($this->host));

        Event::assertDispatched(JobFailed::class);
    }

    public function testDeleteSuccess()
    {
        $this->kingpinServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/hostgroup/hg-test/host/aa:bb:cc:dd:ee:ff')
            ->andReturnUsing(function () {
                return new Response('200', [], json_encode([]));
            });
        $this->kingpinServiceMock()
            ->expects('delete')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/hostgroup/hg-test/host/aa:bb:cc:dd:ee:ff')
            ->andReturnUsing(function () {
                return new Response('200', [], json_encode([]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new DeleteInVmware($this->host));

        Event::assertNotDispatched(JobFailed::class);
    }

}