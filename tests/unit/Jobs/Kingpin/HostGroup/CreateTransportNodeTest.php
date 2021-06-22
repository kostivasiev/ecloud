<?php

namespace Tests\unit\Jobs\Kingpin\HostGroup;

use App\Jobs\Nsx\HostGroup\CreateTransportNodeProfile;
use App\Models\V2\HostGroup;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\Mocks\HostGroup\TransportNodeProfile;
use Tests\TestCase;

class CreateTransportNodeTest extends TestCase
{
    use TransportNodeProfile;

    protected $job;
    protected $hostGroup;

    public function setUp(): void
    {
        parent::setUp();
        $this->hostGroup = factory(HostGroup::class)->create([
            'id' => 'hg-test',
            'name' => 'hg-test',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'host_spec_id' => $this->hostSpec()->id,
            'windows_enabled' => true,
        ]);
        $this->job = \Mockery::mock(CreateTransportNodeProfile::class, [$this->hostGroup])->makePartial();
    }

    public function testNoTransportNodeProfiles()
    {
        $this->transportNodeNoProfiles();
        $this->assertFalse($this->job->handle());
    }

    public function testTransportNodeProfileNameExists()
    {
        $this->transportNodeNameExists('tnp-' . $this->hostGroup->id);
        $this->assertTrue($this->job->handle());
    }

    public function testNoNetworkSwitchDetails()
    {
        $this->networkSwitchNoResults();
        $this->assertFalse($this->job->handle());
    }

    public function testNoTransportZones()
    {
        $this->transportZonesNoResults();
        $this->assertFalse($this->job->handle());
    }

    public function testNoUplinkHostSwitchProfiles()
    {
        $this->uplinkHostNoResults();
        $this->assertFalse($this->job->handle());
    }

    public function testNoVtepIpPools()
    {
        $this->vtepIpPoolNoResults();
        $this->assertFalse($this->job->handle());
    }

    public function testCreateSuccessful()
    {
        $this->validVtepIpPool();
        $this->nsxServiceMock()->expects('post')
            ->withSomeOfArgs('/api/v1/transport-node-profiles')
            ->andReturnUsing(function () {
                return new Response(200);
            });
        $this->assertNull($this->job->handle());
    }
}