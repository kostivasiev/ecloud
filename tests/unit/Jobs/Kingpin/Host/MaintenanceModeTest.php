<?php
namespace Tests\unit\Jobs\Kingpin\Host;

use App\Jobs\Kingpin\Host\MaintenanceMode;
use App\Models\V2\Host;
use App\Models\V2\Sync;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class MaintenanceModeTest extends TestCase
{
    use DatabaseMigrations;

    protected MaintenanceMode $job;
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
        $this->job = new MaintenanceMode($this->host);
    }

    public function testGetMacAddress404()
    {
        $this->conjurerServiceMock()
            ->expects('get')
            ->withSomeOfArgs('/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test')
            ->andThrow(RequestException::create(new Request('GET', ''), new Response(404)));
        Log::shouldReceive('warning')
            ->withSomeOfArgs(MaintenanceMode::class . ' : Host Spec for h-test could not be retrieved.');

        $this->assertFalse($this->job->getMacAddress($this->host, $this->host->hostGroup, $this->host->hostGroup->availabilityZone));
    }

    public function testGetMacAddressEmpty()
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
                            'address' => '',
                            'type' => 'vNIC'
                        ]
                    ]
                ]));
            });
        Log::shouldReceive('error')
            ->withSomeOfArgs('Failed to load eth0 address for host ' . $this->host->id);

        $this->assertFalse($this->job->getMacAddress($this->host, $this->host->hostGroup, $this->host->hostGroup->availabilityZone));
    }

    public function testMaintenanceModeFail()
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
            ->expects('post')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/hostgroup/hg-test/host/00:25:B5:C0:A0:1B/maintenance')
            ->andThrow(RequestException::create(new Request('GET', ''), new Response(404)));
        Log::shouldReceive('info')
            ->withSomeOfArgs(MaintenanceMode::class . ' : Started');
        Log::shouldReceive('info')
            ->withSomeOfArgs('Mac Address: 00:25:B5:C0:A0:1B');
        Log::shouldReceive('error')
            ->withSomeOfArgs('Error while putting Host h-test into maintenance mode.');
        $this->assertFalse($this->job->handle());
    }

    public function testMaintenanceModeSuccess()
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
            ->expects('post')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/hostgroup/hg-test/host/00:25:B5:C0:A0:1B/maintenance')
            ->andReturnUsing(function () {
                return new Response(200);
            });
        $this->assertNull($this->job->handle());
    }

}