<?php

namespace Tests\Unit\Console\Commands\FloatingIp;

use App\Events\V2\Task\Created;
use App\Models\V2\FloatingIpResource;
use Tests\TestCase;

class MigratePolymorphicRelationshipToPivotTest extends TestCase
{
    public function testSuccess()
    {
        $this->markTestSkipped('marked skipped as script has already been run on production');
        $this->floatingIp();
        $this->floatingIp()->resource()->associate($this->ipAddress());
        $this->floatingIp()->save();

        $this->assertCount(0, FloatingIpResource::all());

        $this->artisan('floating-ip:migrate-polymorphic-relationship');

        $this->assertCount(1, FloatingIpResource::all());

        $floatingIpResource = FloatingIpResource::first();

        $this->assertEquals($this->ipAddress()->id, $floatingIpResource->resource->id);
    }

    public function testFloatingIpNotAssignedIgnored()
    {
        $this->markTestSkipped('marked skipped as script has already been run on production');

        $this->assertCount(0, FloatingIpResource::all());

        $this->artisan('floating-ip:migrate-polymorphic-relationship');

        $this->assertCount(0, FloatingIpResource::all());
    }

    public function testPivotExistsSkips()
    {
        $this->markTestSkipped('marked skipped as script has already been run on production');

        $this->floatingIp();
        $this->floatingIp()->resource()->associate($this->ipAddress());
        $this->floatingIp()->save();

        $floatingIpResource = FloatingIpResource::make([
            'floating_ip_id' => $this->floatingIp()->id,
        ]);
        $floatingIpResource->resource()->associate($this->ipAddress());
        $floatingIpResource->save();

        $this->assertCount(1, FloatingIpResource::all());

        $this->artisan('floating-ip:migrate-polymorphic-relationship');

        $this->assertCount(1, FloatingIpResource::all());
    }
}
