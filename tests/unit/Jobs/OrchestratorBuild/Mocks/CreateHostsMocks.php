<?php
namespace Tests\unit\Jobs\OrchestratorBuild\Mocks;

use App\Models\V2\Host;
use GuzzleHttp\Psr7\Response;

trait CreateHostsMocks
{
    public function buildCreateHostIsCreatedMocks()
    {
        app()->bind(Host::class, function () {
            $this->host()->mac_address = 'dc05658baeff';
            $this->host()->saveQuietly();
            return $this->host();
        });
        $this->conjurerServiceMock()
            ->expects('get')
            ->withSomeOfArgs('/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/'.$this->host()->id)
            ->andReturnUsing(function () {
                return new Response(200);
            });
        $this->conjurerServiceMock()
            ->expects('get')
            ->withSomeOfArgs('/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test')
            ->andReturnUsing(function () {
                return new Response(200);
            });
        $this->kingpinServiceMock()
            ->expects('get')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/hostgroup/hg-test/host/dc05658baeff')
            ->andReturnUsing(function () {
                return new Response(200);
            });
        $this->artisanServiceMock()
            ->expects('get')
            ->withSomeOfArgs('/api/v2/san/MCS-E-G0-3PAR-01/host/h-test')
            ->andReturnUsing(function () {
                return new Response(200);
            });
        $this->kingpinServiceMock()
            ->expects('get')
            ->times(3)
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/hostgroup/hg-test/host/dc05658baeff')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(['powerState' => 'poweredOn', 'networkProfileApplied' => true]));
            });
    }
}