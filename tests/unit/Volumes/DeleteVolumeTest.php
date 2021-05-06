<?php

namespace Tests\unit\Volumes;

use App\Jobs\Kingpin\Volume\Undeploy;
use App\Models\V2\Volume;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class DeleteVolumeTest extends TestCase
{
    protected $job;
    protected Volume $volume;

    public function setUp(): void
    {
        parent::setUp();
        $this->job = \Mockery::mock(Undeploy::class)->makePartial();
        $this->vpc();
        $this->availabilityZone();
        $this->volume = Volume::withoutEvents(function () {
            return factory(Volume::class)->create([
                'id' => 'vol-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'vmware_uuid' => 'd7a86079-6b02-4373-b2ca-6ec24fef2f1c',
            ]);
        });
    }

    public function testDeleteVolumeThatExistsInKingpin()
    {
        $this->kingpinServiceMock()->shouldReceive('delete')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/volume/d7a86079-6b02-4373-b2ca-6ec24fef2f1c')
            ->andReturnUsing(function () {
                return new Response(200);
            });
        $this->job->model = $this->volume;
        $this->assertNull($this->job->handle());
    }

    public function testDeleteVolumeThatDoesNotExistInKingpin()
    {
        $this->kingpinServiceMock()->shouldReceive('delete')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/volume/d7a86079-6b02-4373-b2ca-6ec24fef2f1c')
            ->andReturnUsing(function () {
                return new Response(404);
            });
        $this->job->model = $this->volume;
        $this->assertNull($this->job->handle());
    }
}