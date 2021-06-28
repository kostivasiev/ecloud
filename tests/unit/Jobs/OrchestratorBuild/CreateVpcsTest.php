<?php
namespace Tests\unit\Jobs\OrchestratorBuild;

use App\Models\V2\OrchestratorBuild;
use App\Models\V2\OrchestratorConfig;
use App\Models\V2\Task;
use App\Support\Sync;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Symfony\Component\VarDumper\Dumper\ContextProvider\RequestContextProvider;
use Tests\TestCase;

class CreateVpcsTest extends TestCase
{
    protected $job;

    public function setUp(): void
    {
        parent::setUp();

        $this->orchestratorConfig = factory(OrchestratorConfig::class)->create();

        $this->orchestratorBuild = factory(OrchestratorBuild::class)->make();
        $this->orchestratorBuild->orchestratorConfig()->associate($this->orchestratorConfig);
        $this->orchestratorBuild->save();

        $this->task = new Task([
            'id' => 'sync-1',
            'name' => Sync::TASK_NAME_UPDATE,
            'data' => []
        ]);
        $this->task->resource()->associate($this->orchestratorBuild);
    }

    public function testSkipIfMacAddressNotSet()
    {
        exit(print_r($this));



//        $this->host->mac_address = '';
//        $this->host->save();
//
//        Event::fake([JobFailed::class, JobProcessed::class]);
//
//        dispatch(new DeleteInVmware($this->host));
//
//        Event::assertNotDispatched(JobFailed::class);
//        Event::assertDispatched(JobProcessed::class, function ($event) {
//            return !$event->job->isReleased();
//        });
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


    protected function getBuildData()
    {
        return <<<EOM
            {
                "vpc": [
                    {
                        "name": "vpc-1",
                        "region_id": "reg-aaaaaaaa"
                    },
                    {
                        "name": "vpc-2",
                        "region_id": "reg-aaaaaaaa",
                        "console_enabled": true,
                        "advanced_networking": true
                    }
                ],
                "router": [
                    {
                        "vpc_id": "{vpc.0}",
                        "name": "router-1"
                    },
                    {
                        "vpc_id": "{vpc.1}",
                        "name": "router-2",
                        "router_throughput_id": "rtp-ec393951"
                    }
                ],
                "network": [
                    {
                        "router_id": "{router.0}",
                        "name": "network-1"
                    },
                    {
                        "router_id": "{router.1}",
                        "name": "network-2",
                        "subnet": "10.0.0.0\/24"
                    }
                ]
            }
        EOM;
    }
}