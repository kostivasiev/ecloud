<?php

namespace Tests\Unit\Jobs\Image;

use App\Jobs\Image\SyncSoftware;
use Database\Seeders\SoftwareSeeder;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SyncSoftwareTest extends TestCase
{
    public function testBindIpAddress()
    {
        (new SoftwareSeeder())->run();

        $this->image()->software()->sync(['soft-aaaaaaaa']);

        $this->assertEquals(1, $this->image()->software()->count());

        Event::fake([JobFailed::class]);

        dispatch(new SyncSoftware($this->image()));

        Event::assertNotDispatched(JobFailed::class);

        $this->assertEquals(0, $this->image()->software()->count());
    }
}