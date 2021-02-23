<?php

namespace Tests\V2\Instances;

use App\Models\V2\BillingMetric;
use App\Models\V2\Sync;
use App\Models\V2\Volume;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class ToggleBackupTest extends TestCase
{
    use DatabaseMigrations;

    protected $volume;

    public function setUp(): void
    {
        parent::setUp();

        $this->volume = factory(Volume::class)->create([
            'id' => 'vol-aaaaaaaa',
            'vpc_id' => $this->vpc()->id,
            'capacity' => 10,
            'availability_zone_id' => $this->availabilityZone()->id
        ]);

        $this->volume->vpc()->associate($this->vpc());
        $this->volume->instances()->attach($this->instance());
    }

    public function testEnableBackupUpdatesBillingMetrics()
    {
        $this->assertNull(BillingMetric::getActiveByKey($this->instance(), 'backup.quota'));

        $this->patch(
            '/v2/instances/' . $this->instance()->id,
            [
                'backup_enabled' => true,
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeInDatabase(
            'instances',
            [
                'id' => $this->instance()->id,
                'backup_enabled' => true
            ],
            'ecloud'
        )
            ->assertResponseStatus(200);

        Event::assertDispatched(\App\Events\V2\Instance\Saving::class, function ($event) {
            return $event->model->id === $this->instance()->id;
        });

        $resourceSyncListener = \Mockery::mock(\App\Listeners\V2\ResourceSync::class)->makePartial();
        $resourceSyncListener->handle(new \App\Events\V2\Instance\Saving($this->instance()));

        $sync = Sync::where('resource_id', $this->instance()->id)->first();

        $computeChangeListener = \Mockery::mock(\App\Listeners\V2\Instance\ComputeChange::class)->makePartial();
        $computeChangeListener->handle(new \App\Events\V2\Instance\Updated($this->instance()));

        // sync set to complete by the ComputeChange listener
        Event::assertDispatched(\App\Events\V2\Sync\Updated::class, function ($event) use ($sync) {
            return $event->model->id === $sync->id;
        });

        $sync->refresh();

        // Check that the backup billing metric is added
        $updateBackupBillingListener = \Mockery::mock(\App\Listeners\V2\Instance\UpdateBackupBilling::class)->makePartial();
        $updateBackupBillingListener->handle(new \App\Events\V2\Sync\Updated($sync));

        $backupMetric = BillingMetric::getActiveByKey($this->instance(), 'backup.quota');

        $this->assertNotNull($backupMetric);

        $this->assertEquals($this->volume->capacity, $backupMetric->value);
    }

    public function testDisableBackupUpdatesBillingMetrics()
    {
        $this->instance()->backup_enabled = true;
        $this->instance()->save();

        $billingMetric = factory(BillingMetric::class)->create([
            'resource_id' => $this->instance()->id,
            'vpc_id' => $this->vpc()->id,
            'key' => 'backup.quota',
            'value' => $this->volume->capacity
        ]);

        $this->assertNotNull(BillingMetric::getActiveByKey($this->instance(), 'backup.quota'));

        $this->patch(
            '/v2/instances/' . $this->instance()->id,
            [
                'backup_enabled' => false,
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeInDatabase(
            'instances',
            [
                'id' => $this->instance()->id,
                'backup_enabled' => false
            ],
            'ecloud'
        )
            ->assertResponseStatus(200);

        Event::assertDispatched(\App\Events\V2\Instance\Saving::class, function ($event) {
            return $event->model->id === $this->instance()->id;
        });

        $resourceSyncListener = \Mockery::mock(\App\Listeners\V2\ResourceSync::class)->makePartial();
        $resourceSyncListener->handle(new \App\Events\V2\Instance\Saving($this->instance()));

        $sync = Sync::where('resource_id', $this->instance()->id)->first();

        $computeChangeListener = \Mockery::mock(\App\Listeners\V2\Instance\ComputeChange::class)->makePartial();
        $computeChangeListener->handle(new \App\Events\V2\Instance\Updated($this->instance()));

        // sync set to complete by the ComputeChange listener
        Event::assertDispatched(\App\Events\V2\Sync\Updated::class, function ($event) use ($sync) {
            return $event->model->id === $sync->id;
        });

        $sync->refresh();

        // Check that the backup billing metric is ended
        $updateBackupBillingListener = \Mockery::mock(\App\Listeners\V2\Instance\UpdateBackupBilling::class)->makePartial();
        $updateBackupBillingListener->handle(new \App\Events\V2\Sync\Updated($sync));

        $billingMetric->refresh();
        $this->assertNotNull($billingMetric->end);

        $backupMetric = BillingMetric::getActiveByKey($this->instance(), 'backup.quota');
        $this->assertNull($backupMetric);
    }
}
