<?php
namespace Tests\unit\Jobs\Kingpin\Host;

use App\Jobs\Kingpin\Host\DeleteInVmware;
use App\Models\V2\Host;
use App\Models\V2\HostGroup;
use App\Models\V2\Sync;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteInVmwareTest extends TestCase
{
    use DatabaseMigrations;

    protected Host $host;
    protected DeleteInVmware $job;

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
        $this->job = new DeleteInVmware($this->host);
    }

    public function testNoUcsHost()
    {
        $this->conjurerServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test')
            ->andThrow(RequestException::create(new Request('GET', ''), new Response(404)));
        Log::shouldReceive('info')
            ->withSomeOfArgs(DeleteInVmware::class . ' : Started');
        Log::shouldReceive('warning')
            ->withSomeOfArgs(DeleteInVmware::class . ' : Host was not found on UCS, skipping.');
        $this->assertNull($this->job->handle());
    }

    public function testUnableToDelete()
    {
        $this->conjurerServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test')
            ->andReturnUsing(function () {
                return new Response('200', [], json_encode([
                    'specification' => 'DUAL-4208--32GB',
                    'name' => 'DUAL-4208--32GB',
                    'interfaces' => [
                        [
                            'name' => 'eth0',
                            'address' => '00:25:B5:C0:A0:1B',
                            'type' => 'vNIC'
                        ]
                    ]
                ]));
            });
        $this->kingpinServiceMock()
            ->expects('delete')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/hostgroup/hg-test/host/00:25:B5:C0:A0:1B')
            ->andThrow(RequestException::create(new Request('DELETE', ''), new Response(404)));
        Log::shouldReceive('info')
            ->withSomeOfArgs(DeleteInVmware::class . ' : Started');
        Log::shouldReceive('debug')
            ->withSomeOfArgs('MAC address: 00:25:B5:C0:A0:1B');
        Log::shouldReceive('warning')
            ->withSomeOfArgs(DeleteInVmware::class . ' : Host could not be deleted, skipping.');

        $this->assertNull($this->job->handle());
    }

    public function testDeleteSuccess()
    {
        $this->conjurerServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test')
            ->andReturnUsing(function () {
                return new Response('200', [], json_encode([
                    'specification' => 'DUAL-4208--32GB',
                    'name' => 'DUAL-4208--32GB',
                    'interfaces' => [
                        [
                            'name' => 'eth0',
                            'address' => '00:25:B5:C0:A0:1B',
                            'type' => 'vNIC'
                        ]
                    ]
                ]));
            });
        $this->kingpinServiceMock()
            ->expects('delete')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/hostgroup/hg-test/host/00:25:B5:C0:A0:1B')
            ->andReturnUsing(function () {
                return new Response(200);
            });

        $this->assertNull($this->job->handle());
    }

}