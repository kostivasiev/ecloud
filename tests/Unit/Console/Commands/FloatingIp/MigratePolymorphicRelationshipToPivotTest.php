<?php

namespace Tests\Unit\Console\Commands\FloatingIp;

use App\Models\V2\FloatingIpResource;
use Tests\TestCase;

class MigratePolymorphicRelationshipToPivotTest extends TestCase
{
    public function testSuccess()
    {
        $this->floatingIp()
        ->setAttribute('resource_id', $this->ipAddress()->id)
        ->setAttribute('resource_type', $this->ipAddress()->getMorphClass())
        ->save();

        $this->assertCount(0, FloatingIpResource::all());

        $this->artisan('floating-ip:migrate-polymorphic-relationship');

        $this->assertCount(1, FloatingIpResource::all());

        $floatingIpResource = FloatingIpResource::first();

        $this->assertEquals($this->ipAddress()->id, $floatingIpResource->resource->id);
    }

    public function testFloatingIpNotAssignedIgnored()
    {
        $this->assertCount(0, FloatingIpResource::all());

        $this->artisan('floating-ip:migrate-polymorphic-relationship');

        $this->assertCount(0, FloatingIpResource::all());
    }

    public function testPivotExistsSkips()
    {
        $this->floatingIp()
            ->setAttribute('resource_id', $this->ipAddress()->id)
            ->setAttribute('resource_type', $this->ipAddress()->getMorphClass())
            ->save();

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
