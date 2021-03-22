<?php

namespace Tests\V2\Host\Mocks;

use GuzzleHttp\Psr7\Response;

trait ConjurerMocks
{
    public function conjurerCheckExistsMock(bool $fail = false)
    {
        $responseCode = ($fail) ? 500 : 200;
        // Conjurer Mock for retrieving MAC Address
        $this->conjurerServiceMock()->shouldReceive('get')
            ->withSomeofArgs('/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test')
            ->andReturnUsing(function () use ($responseCode) {
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

    public function conjurerPowerOffMock(bool $fail = false)
    {
        $responseCode = ($fail) ? 500 : 200;
        $this->conjurerServiceMock()->shouldReceive('delete')
            ->withSomeOfArgs('/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test/power')
            ->andReturnUsing(function () use ($responseCode) {
                return new Response($responseCode);
            });
        return $this;
    }

    public function conjurerDeleteServiceProfileMock(bool $fail = false)
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