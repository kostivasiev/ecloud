<?php

namespace Tests\unit\Jobs\Kingpin\HostGroup;

use Tests\Mocks\HostGroup\DeleteClusterJob;
use Tests\TestCase;

class DeleteClusterTest extends TestCase
{
    use DeleteClusterJob;

    public function setUp(): void
    {
        parent::setUp();
        $this->deleteClusterSetup();
    }

    public function testHostGroupNotExists()
    {
        $this->hostGroupNotExists();
        $this->assertFalse($this->deleteCluster->handle());
    }

    public function testHostGroupDeleteFails()
    {
        $this->deleteHostGroupFails();
        $this->assertFalse($this->deleteCluster->handle());
    }

    public function testHostGroupDeleteSuccess()
    {
        $this->deleteHostGroupSuccess();
        $this->assertNull($this->deleteCluster->handle());
    }
}