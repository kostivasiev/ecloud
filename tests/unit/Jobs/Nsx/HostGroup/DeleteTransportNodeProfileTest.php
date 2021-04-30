<?php
namespace Tests\unit\Jobs\Nsx\HostGroup;

use App\Jobs\Nsx\HostGroup\DeleteTransportNodeProfile;
use App\Models\V2\HostGroup;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\Mocks\HostGroup\TransportNodeProfile;
use Tests\TestCase;

class DeleteTransportNodeProfileTest extends TestCase
{
    use DatabaseMigrations, TransportNodeProfile;

    protected $job;
    protected $hostGroup;

    public function setUp(): void
    {
        parent::setUp();
        $this->hostGroup = HostGroup::withoutEvents(function () {
            return factory(HostGroup::class)->create([
                'id' => 'hg-test',
                'name' => 'hg-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'host_spec_id' => $this->hostSpec()->id,
                'windows_enabled' => true,
            ]);
        });
        $this->job = \Mockery::mock(DeleteTransportNodeProfile::class, [$this->hostGroup])->makePartial();
    }

    public function testNoComputeCollectionFound()
    {
        $this->noComputeCollectionItem();
        $this->assertFalse($this->job->handle());
    }

    public function testNoTransportNodeCollection()
    {
        $this->noTransportNodeCollectionItem();
        $this->assertFalse($this->job->handle());
    }

    public function testDetachNodeUnsuccessful()
    {
        $this->detachNodeFail();
        $this->assertFalse($this->job->handle());
    }

    public function testDeleteNodeUnsuccessful()
    {
        $this->deleteNodeFail();
        $this->assertFalse($this->job->handle());
    }

    public function testSuccessful()
    {
        $this->deleteNodeSuccessful();
        $this->assertNull($this->job->handle());
    }
}