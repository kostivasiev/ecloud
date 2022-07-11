<?php

namespace Tests\Unit\Jobs\Instance\Deploy;

use App\Jobs\Instance\Deploy\AwaitHostGroup;
use App\Models\V2\AvailabilityZone;
use Database\Seeders\ResourceTierSeeder;
use Database\Seeders\VpcSeeder;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AwaitHostGroupTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $availabilityZone = AvailabilityZone::factory()->create([
            'id' => 'az-aaaaaaaa',
            'region_id' => $this->region()->id,
        ]);
        (new VpcSeeder())->run();
        (new ResourceTierSeeder())->run();
        $this->instanceModel()
            ->setAttribute('availability_zone_id', $availabilityZone->id)->saveQuietly();
    }

    public function testSuccessful()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AwaitHostGroup($this->instanceModel()));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });

        $this->instanceModel()->hostGroup()->associate($this->hostGroup())->save();

        dispatch(new AwaitHostGroup($this->instanceModel()));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }
}
