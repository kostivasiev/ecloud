<?php

namespace Tests\Unit\Listeners\V2\AvailabilityZoneCapacity;

use App\Mail\AvailabilityZoneCapacityAlert;
use App\Models\V2\AvailabilityZoneCapacity;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendAlertTest extends TestCase
{
    protected \Faker\Generator $faker;
    protected $floatingIp;
    protected $availabilityZoneCapacity;

    public function setUp(): void
    {
        parent::setUp();

        $this->availabilityZoneCapacity = AvailabilityZoneCapacity::factory()->create([
            'availability_zone_id' => $this->availabilityZone()->id,
            'current' => null
        ]);
    }

    public function testCapacityUpdateNoAlertIsTriggered()
    {
        Mail::fake();

        $this->availabilityZoneCapacity->current = 10;
        $this->availabilityZoneCapacity->save();

        $sendAlertListener = \Mockery::mock(\App\Listeners\V2\AvailabilityZoneCapacity\SendAlert::class)->makePartial();
        $sendAlertListener->handle(new \App\Events\V2\AvailabilityZoneCapacity\Saved($this->availabilityZoneCapacity));

        Mail::assertNothingSent();
    }

    public function testCapacityUpdateWarningAlertIsTriggered()
    {
        Mail::fake();

        $this->availabilityZoneCapacity->current = 70;
        $this->availabilityZoneCapacity->save();

        $sendAlertListener = \Mockery::mock(\App\Listeners\V2\AvailabilityZoneCapacity\SendAlert::class)->makePartial();
        $sendAlertListener->handle(new \App\Events\V2\AvailabilityZoneCapacity\Saved($this->availabilityZoneCapacity));

        Mail::assertSent(AvailabilityZoneCapacityAlert::class, function ($alert) {
            return $alert->alertLevel = AvailabilityZoneCapacityAlert::ALERT_LEVEL_WARNING;
        });
    }

    public function testCapacityUpdateCriticalAlertIsTriggered()
    {
        Mail::fake();

        $this->availabilityZoneCapacity->current = 85;
        $this->availabilityZoneCapacity->save();

        $sendAlertListener = \Mockery::mock(\App\Listeners\V2\AvailabilityZoneCapacity\SendAlert::class)->makePartial();
        $sendAlertListener->handle(new \App\Events\V2\AvailabilityZoneCapacity\Saved($this->availabilityZoneCapacity));

        Mail::assertSent(AvailabilityZoneCapacityAlert::class, function ($alert) {
            return $alert->alertLevel = AvailabilityZoneCapacityAlert::ALERT_LEVEL_CRITICAL;
        });
    }
}
