<?php

namespace Tests\unit\Jobs\Nsx\HostGroup;

use App\Jobs\Nsx\HostGroup\DeleteTransportNodeProfile;
use Tests\Mocks\Traits\HostGroup\DeleteTransportNodeProfileJob;
use Tests\TestCase;

class DeleteTransportNodeProfileTest extends TestCase
{
    use DeleteTransportNodeProfileJob;

    public function setUp(): void
    {
        parent::setUp();
        $this->transportNodeSetup();
    }

    public function testInvalidComputeCollectionItem()
    {
        $this->computeCollectionItemNull();
        $this->assertFalse($this->deleteTransportNode->handle());
    }

    public function testInvalidTransportNodeCollection()
    {
        $this->transportNodeCollectionNull();
        $this->assertFalse($this->deleteTransportNode->handle());
    }

    public function testFailedDetach()
    {
        $this->detachNodeFail();
        $this->assertFalse($this->deleteTransportNode->handle());
    }

    public function testFailedDelete()
    {
        $this->deleteNodeFail();
        $this->assertFalse($this->deleteTransportNode->handle());
    }

    public function testSuccessfulDelete()
    {
        $this->deleteNodeSuccess();
        $this->assertNull($this->deleteTransportNode->handle());
    }
}