<?php

namespace Tests\Unit\Jobs\Nic;

use App\Jobs\Nic\DisassociateIp;
use Tests\TestCase;

class DisassociateIpTest extends TestCase
{
    public $job;
    public ?string $message = null;

    public function setUp(): void
    {
        parent::setUp();
        $this->job = \Mockery::mock(DisassociateIp::class)->makePartial();
        $this->job->allows('info')
            ->with(\Mockery::capture($this->message))
            ->andReturnTrue();
    }

    public function testSuccess()
    {
        $this->job->task = $this->createSyncUpdateTask(
            $this->nic(),
            [
                'ip_address_id' => $this->ipAddress()->id
            ]
        );
        $this->job->handle();

        $this->nic()->refresh();
        $this->assertTrue(
            $this->nic()
                ->ipAddresses()
                ->where('id', $this->ipAddress()->id)
                ->count() == 0
        );
    }

    public function testFailure()
    {
        $this->job->task = $this->createSyncUpdateTask(
            $this->nic(),
            [
                'ip_address_id' => 'ip-zzzzzzzz-dev',
            ]
        );
        $this->job->allows('fail')
            ->with(\Mockery::capture($this->message))
            ->andReturnTrue();

        $this->job->handle();
        $this->assertNotNull($this->message);
    }
}
