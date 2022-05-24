<?php

namespace Tests\Unit\Console\Commands\FloatingIp;

use App\Events\V2\Task\Created;
use App\Models\V2\FloatingIpResource;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MigratePolymorphicRelationshipToPivotTest extends TestCase
{
    public function testSuccess()
    {
        Event::fake([Created::class]);

        $this->floatingIp();
        $this->floatingIp()->resource()->associate($this->ip());
        $this->floatingIp()->save();

        $this->assertCount(0, FloatingIpResource::all());

        $this->artisan('floating-ip:migrate-polymorphic-relationship');

        $this->assertCount(1, FloatingIpResource::all());

        $floatingIpResource = FloatingIpResource::first();

        $this->assertEquals($this->ip()->id, $floatingIpResource->resource->id);
    }

    public function testFloatingIpNotAssignedIgnored()
    {
        Event::fake([Created::class]);

        $this->assertCount(0, FloatingIpResource::all());

        $this->artisan('floating-ip:migrate-polymorphic-relationship');

        $this->assertCount(0, FloatingIpResource::all());
    }
}
