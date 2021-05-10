<?php

namespace Tests\unit\Models;

use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class InstanceTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testSaveFiresExpectedEvents()
    {
        Event::fake();

        $this->instance()->vcpu_cores = 2;
        $this->instance()->save();

        Event::assertDispatched(\App\Events\V2\Instance\Updated::class, function ($event)  {
            return $event->model->id === $this->instance()->id;
        });

        Event::assertDispatched(\App\Events\V2\Instance\Saving::class, function ($event)  {
            return $event->model->id === $this->instance()->id;
        });

        Event::assertDispatched(\App\Events\V2\Instance\Saved::class, function ($event)  {
            return $event->model->id === $this->instance()->id;
        });
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
