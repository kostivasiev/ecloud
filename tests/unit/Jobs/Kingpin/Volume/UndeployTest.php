<?php

namespace Tests\unit\Jobs\Kingpin\Volume;

use App\Jobs\Kingpin\Volume\Undeploy;
use App\Models\V2\Volume;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UndeployTest extends TestCase
{
    protected $volume;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testSucceeds()
    {
        Volume::withoutEvents(function() {
            $this->volume = Volume::factory()->createOne([
                'id' => 'vol-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'vmware_uuid' => 'uuid-test-uuid-test-uuid-test',
            ]);
        });

        $this->kingpinServiceMock()->expects('delete')
            ->withArgs(['/api/v2/vpc/vpc-test/volume/uuid-test-uuid-test-uuid-test'])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        Event::fake();

        dispatch(new Undeploy($this->volume));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testDataVolumeWithInstanceAttachedFails()
    {
        Volume::withoutEvents(function() {
            $this->volume = Volume::factory()->create([
                'id' => 'vol-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'vmware_uuid' => 'uuid-test-uuid-test-uuid-test',
            ]);

            $this->instanceModel()->volumes()->attach($this->volume);
        });

        $this->expectException(\Exception::class);

        dispatch(new Undeploy($this->volume));
    }

    public function testOSVolumeWithInstanceAttachedSucceeds()
    {
        Volume::withoutEvents(function() {
            $this->volume = Volume::factory()->create([
                'id' => 'vol-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'vmware_uuid' => 'uuid-test-uuid-test-uuid-test',
                'os_volume' => true,
            ]);
        });

        $this->kingpinServiceMock()->expects('delete')
            ->withArgs(['/api/v2/vpc/vpc-test/volume/uuid-test-uuid-test-uuid-test'])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        Event::fake();

        dispatch(new Undeploy($this->volume));

        Event::assertNotDispatched(JobFailed::class);
    }
}
