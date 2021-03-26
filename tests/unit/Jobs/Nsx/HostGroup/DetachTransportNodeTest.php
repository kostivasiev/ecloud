<?php

namespace Tests\unit\Jobs\Nsx\HostGroup;

use App\Jobs\Nsx\HostGroup\DetachTransportNode;
use Tests\Mocks\Traits\HostGroup\DetachTransportNodeJob;
use Tests\TestCase;

class DetachTransportNodeTest extends TestCase
{
    use DetachTransportNodeJob;

    public function setUp(): void
    {
        parent::setUp();
        $this->transportNodeSetup();
    }

    public function testInvalidComputeCollectionItem()
    {
        $this->computeCollectionItemNull();
        $this->assertFalse($this->detachTransportNode->handle());
    }

    public function testInvalidTransportNodeCollection()
    {
        $this->transportNodeCollectionNull();
        $this->assertFalse($this->detachTransportNode->handle());
    }

    public function testFailedDelete()
    {
        $this->detachNodeFail();
        $this->assertFalse($this->detachTransportNode->handle());
    }

    public function testSuccessfulDelete()
    {
        $this->detachNodeSuccess();
        $this->assertNull($this->detachTransportNode->handle());
    }
}