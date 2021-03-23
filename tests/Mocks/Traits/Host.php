<?php

namespace Tests\Mocks\Traits;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

trait Host
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

    /**
     * Mock that the host already exists on Update, so that we don't run the create jobs
     */
    protected function syncSaveIdempotent()
    {
        $this->conjurerServiceMock()->expects('get')
            ->withArgs(['/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test'])
            ->andReturnUsing(function () {
                return new Response(200);
            });
    }

    public function deleteHostMocks()
    {
        $this->checkExists()
            ->checkOnline()
            ->maintenanceModeOn()
            ->powerOff()
            ->removeHostfrom3Par()
            ->removeFromHostGroup()
            ->deleteServiceProfile();
    }

    protected function checkExists(bool $fail = false)
    {
        $responseCode = ($fail) ? 500 : 200;
        // Conjurer Mock for retrieving MAC Address
        $this->conjurerServiceMock()->shouldReceive('get')
            ->withSomeofArgs('/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test')
            ->andReturnUsing(function () use ($responseCode) {
                if ($responseCode === 500) {
                    return new Response($responseCode);
                }
                return new Response($responseCode, [], json_encode(
                    [
                        'specification' => 'DUAL-4208--32GB',
                        'name' => 'DUAL-4208--32GB',
                        'interfaces' => [
                            [
                                'name' => 'eth0',
                                'address' => '00:25:B5:C0:A0:1B',
                                'type' => 'vNIC'
                            ]
                        ]
                    ]
                ));
            });
        return $this;
    }

    protected function checkOnline(bool $fail = false)
    {
        $responseCode = ($fail) ? 500 : 200;
        $this->kingpinServiceMock()->shouldReceive('get')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/hostgroup/hg-test/host/00:25:B5:C0:A0:1B')
            ->andReturnUsing(function () use ($responseCode) {
                return new Response($responseCode);
            });
        return $this;
    }

    protected function maintenanceModeOn(bool $fail = false)
    {
        $responseCode = ($fail) ? 500 : 200;
        $this->kingpinServiceMock()->shouldReceive('post')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/hostgroup/hg-test/host/00:25:B5:C0:A0:1B/maintenance')
            ->andReturnUsing(function () use ($responseCode) {
                return new Response($responseCode);
            });
        return $this;
    }

    protected function powerOff(bool $fail = false)
    {
        $responseCode = ($fail) ? 500 : 200;
        $this->conjurerServiceMock()->shouldReceive('delete')
            ->withSomeOfArgs('/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test/power')
            ->andReturnUsing(function () use ($responseCode) {
                return new Response($responseCode);
            });
        return $this;
    }

    protected function removeHostfrom3Par(bool $fail = false)
    {
        $responseCode = ($fail) ? 500 : 200;
        $this->artisanServiceMock()->shouldReceive('delete')
            ->withSomeOfArgs('/api/v2/san/' . $this->availabilityZone()->san_name . '/host/' . $this->host()->id)
            ->andReturnUsing(function () use ($responseCode) {
                return new Response($responseCode);
            });
        return $this;
    }

    protected function removeFromHostGroup(bool $fail = false)
    {
        $responseCode = ($fail) ? 500 : 200;
        $this->kingpinServiceMock()->shouldReceive('delete')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/hostgroup/hg-test/host/00:25:B5:C0:A0:1B')
            ->andReturnUsing(function () use ($responseCode) {
                return new Response($responseCode);
            });
        return $this;
    }

    protected function deleteServiceProfile(bool $fail = false)
    {
        $responseCode = ($fail) ? 500 : 200;
        $this->conjurerServiceMock()->shouldReceive('delete')
            ->withSomeOfArgs('/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test')
            ->andReturnUsing(function () use ($responseCode) {
                return new Response($responseCode);
            });
        return $this;
    }
}