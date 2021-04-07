<?php

namespace Tests\Mocks\Host;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

trait Mocks
{
    /** @var \App\Models\V2\Host */
    private $host;

    public function host()
    {
        if (!$this->host) {
            $this->createHostMocks();
            $this->host = factory(\App\Models\V2\Host::class)->create([
                'id' => 'h-test',
                'name' => 'h-test',
                'host_group_id' => $this->hostGroup()->id,
            ]);
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
                // Empty array means no stock available, array count indicates stock available
                return new Response(200, [], json_encode([
                    'specification' => 'DUAL-4208--32GB',
                    'name' => 'DUAL-4208--32GB',
                    'interfaces' => [
                        'name' => 'eth0',
                        'address' => '00:25:B5:C0:A0:1B',
                        'type' => 'vNIC'
                    ]
                ]));
            });
    }

    protected function createAutoDeployRule()
    {
        $this->conjurerServiceMock()->expects('get')
            ->withArgs(['/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test'])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'specification' => 'DUAL-4208--32GB',
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
                ]));
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
                return new Response(200, [], json_encode([
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
                ]));
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

    /**
     * Mock that the host already exists on Update, so that we don't run the create jobs
     * @param string $id
     */
    protected function syncSaveIdempotent($id = 'h-test')
    {
        $this->conjurerServiceMock()->expects('get')
            ->withArgs(['/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/' . $id])
            ->andReturnUsing(function () {
                return new Response(200);
            });
    }
}