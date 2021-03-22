<?php

namespace Tests\V2\Host\Mocks;

use GuzzleHttp\Psr7\Response;

trait KingpinMocks
{
    public function kingpinCheckOnlineMock(bool $fail = false)
    {
        $responseCode = ($fail) ? 500 : 200;
        $this->kingpinServiceMock()->shouldReceive('get')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/hostgroup/hg-test/host/00:25:B5:C0:A0:1B')
            ->andReturnUsing(function () use ($responseCode) {
                return new Response($responseCode);
            });
        return $this;
    }

    public function kingpinMaintenanceModeOnMock(bool $fail = false)
    {
        $responseCode = ($fail) ? 500 : 200;
        $this->kingpinServiceMock()->shouldReceive('post')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/hostgroup/hg-test/host/00:25:B5:C0:A0:1B/maintenance')
            ->andReturnUsing(function () use ($responseCode) {
                return new Response($responseCode);
            });
        return $this;
    }

    public function kingpinRemoveFromHostGroupMock(bool $fail = false)
    {
        $responseCode = ($fail) ? 500 : 200;
        $this->kingpinServiceMock()->shouldReceive('delete')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/hostgroup/hg-test/host/00:25:B5:C0:A0:1B')
            ->andReturnUsing(function () use ($responseCode) {
                return new Response($responseCode);
            });
        return $this;
    }
}