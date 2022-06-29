<?php

namespace Tests\Unit\Jobs\Instance\Deploy;

use App\Jobs\Instance\Deploy\AssignSharedHostGroup;
use App\Models\V2\AvailabilityZone;
use Database\Seeders\ResourceTierSeeder;
use Database\Seeders\VpcSeeder;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AssignSharedHostGroupTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->availabilityZone = AvailabilityZone::factory()->create([
            'id' => 'az-aaaaaaaa',
            'region_id' => $this->region()->id,
        ]);
        (new VpcSeeder())->run();
        (new ResourceTierSeeder())->run();
        $this->instanceModel()
            ->setAttribute('availability_zone_id', $this->availabilityZone->id)->saveQuietly();
    }

    public function testSuccessful()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AssignSharedHostGroup($this->instanceModel()));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        $this->instanceModel()->refresh();

        $this->assertEquals('hg-standard-cpu', $this->instanceModel()->deploy_data['hostGroupId']);
    }
}
