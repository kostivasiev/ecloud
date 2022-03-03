<?php

namespace Tests\Mocks\Host;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use App\Models\V2\Host;

trait Mocks
{
    /** @var \App\Models\V2\Host */
    private $host;

    public function host()
    {
        if (!$this->host) {
            $this->host = Model::withoutEvents(function() {
               return Host::factory()->create([
                   'id' => 'h-test',
                   'name' => 'h-test',
                   'host_group_id' => $this->hostGroup()->id,
               ]);
            });
        }
        return $this->host;
    }

    public function createHostMocks()
    {
        $this->syncSave();
        $this->createLanPolicy();
        $this->checkAvailableCompute();
        $this->createProfile();
        $this->createAutoDeployRule();
        $this->deploy();
        $this->powerOn();
        $this->checkOnline();
    }

    protected function syncSave()
    {
        $this->conjurerServiceMock()->expects('get')->once()
            ->withArgs(['/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test'])
            ->andThrow(
                new RequestException('Not Found', new Request('GET', 'test'), new Response(404))
            );
    }

    protected function createLanPolicy()
    {
        // Check whether a LAN connectivity policy exists on the UCS for the VPC
        $this->conjurerServiceMock()->expects('get')
            ->withArgs(['/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test'])
            ->andThrow(
                new RequestException('Not Found', new Request('GET', 'test'), new Response(404))
            );

        // Create LAN Policy
        $this->conjurerServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/compute/GC-UCS-FI2-DEV-A/vpc',
                [
                    'json' => [
                        'vpcId' => 'vpc-test'
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });
    }

    protected function checkAvailableCompute()
    {
        $this->conjurerServiceMock()->expects('get')
            ->withArgs(['/api/v2/compute/GC-UCS-FI2-DEV-A/specification/test-host-spec/host/available'])
            ->andReturnUsing(function () {
                // Empty array means no stock available, array count indicates stock available
                return new Response(200, [], json_encode([
                    [
                        'specification' => 'DUAL-4208--32GB',
                        'name' => 'DUAL-4208--32GB',
                        'interfaces' => [
                            'name' => 'eth0',
                            'address' => '00:25:B5:C0:A0:1B',
                            'type' => 'vNIC'
                        ]
                    ]
                ]));
            });
    }

    protected function createProfile()
    {
        $this->conjurerServiceMock()->expects('post')
            ->withArgs(['/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/' . $this->vpc()->id .'/host',
                [
                    'json' => [
                        'specificationName' => 'test-host-spec',
                        'hostId' => 'h-test'
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], $this->getHostResponse());
            });
    }

    protected function createAutoDeployRule()
    {
        $this->conjurerServiceMock()->expects('get')
            ->withArgs(['/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test'])
            ->andReturnUsing(function () {
                return new Response(200, [], $this->getHostResponse());
            });

        $this->kingpinServiceMock()->expects('post')
            ->withArgs(['/api/v2/vpc/vpc-test/hostgroup/hg-test/host',
                [
                    'json' => [
                        'hostId' => 'h-test',
                        'hardwareVersion' => 'M4',
                        'macAddress' => '00:25:B5:C0:A0:1B'
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });
    }

    protected function deploy()
    {
        $this->artisanServiceMock()->expects('get')
            ->withArgs(['/api/v2/san/' . $this->availabilityZone()->san_name .'/host/h-test'])
            ->andThrow(
                new RequestException('Not Found', new Request('GET', 'test'), new Response(404))
            );

        $this->conjurerServiceMock()->expects('get')
            ->withArgs(['/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test'])
            ->andReturnUsing(function () {
                return new Response(200, [], $this->getHostResponse());
            });

        $this->artisanServiceMock()->expects('post')
            ->withArgs(['/api/v2/san/MCS-E-G0-3PAR-01/host',
                [
                    'json' => [
                        'hostId' => 'h-test',
                        'fcWWNs' => [
                            '20:00:00:25:B5:C0:A0:0F',
                            '20:00:00:25:B5:C0:B0:0F'
                        ],
                        'osType' => 'VMWare'
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'name' => 'h-test'
                ]));
            });
    }

    protected function powerOn()
    {
        $this->conjurerServiceMock()->expects('post')
            ->withArgs(['/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test/power'])
            ->andReturnUsing(function () {
                return new Response(200);
            });
    }

    protected function checkOnline()
    {
        $this->conjurerServiceMock()->expects('get')
            ->withArgs(['/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test'])
            ->andReturnUsing(function () {
                return new Response(200, [], $this->getHostResponse());
            });

        $this->kingpinServiceMock()->expects('get')
            ->withArgs(['/api/v2/vpc/vpc-test/hostgroup/hg-test/host/00:25:B5:C0:A0:1B'])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'name' => '172.19.0.38',
                    'connectionState' => 'connected',
                    'powerState' => 'poweredOn',
                    'macAddress' => '00:25:B5:C0:A0:1B',
                ]));
            });
        return $this;
    }

    private function getHostResponse()
    {
        return json_encode([
            'name' => 'DUAL-4208--32GB',
            'hardwareVersion' => 'M4',
            'interfaces' => [
                [
                    'name' => 'eth0',
                    'address' => '00:25:B5:C0:A0:1B',
                    'type' => 'vNIC'
                ],
                [
                    'name' => 'eth1',
                    'address' => '00:25:B5:C0:B0:11',
                    'type' => 'vNIC'
                ],
                [
                    'name' => 'eth2',
                    'address' => '00:25:B5:C0:A0:10',
                    'type' => 'vNIC'
                ],
                [
                    'name' => 'fc0',
                    'address' => '20:00:00:25:B5:C0:A0:0F',
                    'type' => 'vHBA'
                ],
                [
                    'name' => 'fc1',
                    'address' => '20:00:00:25:B5:C0:B0:0F',
                    'type' => 'vHBA'
                ]
            ]
        ]);
    }

    public function deleteHostMocks()
    {
        $this->maintenanceModeOn();
        $this->deleteInVmWare();
        $this->powerOff();
        $this->removeFrom3Par();
        $this->deleteServiceProfile();
    }

    public function maintenanceModeOn()
    {
        $this->conjurerServiceMock()
            ->expects('get')
            ->withSomeOfArgs(
                '/api/v2/compute/' . $this->availabilityZone()->ucs_compute_name .
                '/vpc/' . $this->vpc()->id . '/host/h-test'
            )->andReturnUsing(function () {
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
            ->withSomeOfArgs(
                '/api/v2/vpc/' . $this->vpc()->id . '/hostgroup/' . $this->hostGroup()->id . '/host/00:25:B5:C0:A0:1B/maintenance'
            )->andReturnUsing(function () {
                return new Response(200);
            });
    }

    public function deleteInVmWare()
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
    }

    public function powerOff()
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
    }

    public function removeFrom3Par()
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
    }

    public function deleteServiceProfile()
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
    }
}