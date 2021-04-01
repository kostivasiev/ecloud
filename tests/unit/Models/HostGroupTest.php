<?php

namespace Tests\unit\Models;

use App\Models\V2\HostGroup;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class HostGroupTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testSaveFiresExpectedEvents()
    {
        Event::fake();

        HostGroup::withoutEvents(function () {
            factory(HostGroup::class)->create([
                'id' => 'hg-test',
                'name' => 'hg-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'host_spec_id' => $this->hostSpec()->id,
            ]);
        });



        //$hostGroup->save();



//        Event::assertDispatched(\App\Events\V2\HostGroup\Saved::class, function ($event) use ($hostGroup)  {
//            return $event->model->id === $hostGroup->id;
//        });
    }

    public function testDeleteFiresExpectedEvents()
    {
        Event::fake();

        $this->instance()->delete();

        Event::assertDispatched(\App\Events\V2\Instance\Deleting::class, function ($event)  {
            return $event->model->id === $this->instance()->id;
        });

        Event::assertDispatched(\App\Events\V2\Instance\Deleted::class, function ($event)  {
            return $event->model->id === $this->instance()->id;
        });
    }
}
