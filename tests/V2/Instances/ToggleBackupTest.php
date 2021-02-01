<?php

namespace Tests\V2\Instances;

use App\Models\V2\Appliance;
use App\Models\V2\ApplianceVersion;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\BillingMetric;
use App\Models\V2\Instance;
use App\Models\V2\Region;
use App\Models\V2\Sync;
use App\Models\V2\Volume;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class ToggleBackupTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected $vpc;
    protected $instance;
    protected $appliance;
    protected $applianceVersion;
    protected $region;
    protected $availabilityZone;
    protected $volume;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->region = factory(Region::class)->create();
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey()
        ]);

        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey()
        ]);

        Model::withoutEvents(function () {
            $this->volume = factory(Volume::class)->create([
                'id' => 'vol-aaaaaaaa',
                'vpc_id' => $this->vpc->getKey(),
                'capacity' => 10,
                'availability_zone_id' => $this->availabilityZone->getKey()
            ]);
        });

        $this->appliance = factory(Appliance::class)->create([
            'appliance_name' => 'Test Appliance',
        ])->refresh();
        $this->applianceVersion = factory(ApplianceVersion::class)->create([
            'appliance_version_appliance_id' => $this->appliance->id,
        ])->refresh();

        $this->instance = factory(Instance::class)->create([
            'vpc_id' => $this->vpc->getKey(),
            'vcpu_cores' => 1,
            'ram_capacity' => 1024,
            'appliance_version_id' => $this->applianceVersion->uuid,
            'backup_enabled' => false,
        ]);

        $this->volume->vpc()->associate($this->vpc);
        $this->volume->instances()->attach($this->instance);
    }

    public function testEnableBackupUpdatesBillingMetrics()
    {
        $this->assertNull(BillingMetric::getActiveByKey($this->instance, 'backup.quota'));

        $this->patch(
            '/v2/instances/' . $this->instance->getKey(),
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
                'id' => $this->instance->getKey(),
                'backup_enabled'=> true
            ],
            'ecloud'
        )
            ->assertResponseStatus(200);

        Event::assertDispatched(\App\Events\V2\Instance\Saving::class, function ($event) {
            return $event->model->id === $this->instance->getKey();
        });

        $resourceSyncListener = \Mockery::mock(\App\Listeners\V2\ResourceSync::class)->makePartial();
        $resourceSyncListener->handle(new \App\Events\V2\Instance\Saving($this->instance));

        $sync = Sync::where('resource_id', $this->instance->getKey())->first();

        $computeChangeListener = \Mockery::mock(\App\Listeners\V2\Instance\ComputeChange::class)->makePartial();
        $computeChangeListener->handle(new \App\Events\V2\Instance\Updated($this->instance));

        // sync set to complete by the ComputeChange listener
        Event::assertDispatched(\App\Events\V2\Sync\Updated::class, function ($event) use ($sync) {
            return $event->model->id === $sync->id;
        });

        $sync->refresh();

        // Check that the backup billing metric is added
        $updateBackupBillingListener = \Mockery::mock(\App\Listeners\V2\Instance\UpdateBackupBilling::class)->makePartial();
        $updateBackupBillingListener->handle(new \App\Events\V2\Sync\Updated($sync));

        $backupMetric = BillingMetric::getActiveByKey($this->instance, 'backup.quota');

        $this->assertNotNull($backupMetric);

        $this->assertEquals($this->volume->capacity, $backupMetric->value);
    }

    public function testDisableBackupUpdatesBillingMetrics()
    {
        Model::withoutEvents(function () {
            $this->instance->backup_enabled = true;
            $this->instance->save();
        });

        $billingMetric = factory(BillingMetric::class)->create([
            'resource_id' => $this->instance->getKey(),
            'vpc_id' => $this->vpc->getKey(),
            'key' => 'backup.quota',
            'value' => $this->volume->capacity
        ]);

        $this->assertNotNull(BillingMetric::getActiveByKey($this->instance, 'backup.quota'));

        $this->patch(
            '/v2/instances/' . $this->instance->getKey(),
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
                'id' => $this->instance->getKey(),
                'backup_enabled'=> false
            ],
            'ecloud'
        )
            ->assertResponseStatus(200);

        Event::assertDispatched(\App\Events\V2\Instance\Saving::class, function ($event) {
            return $event->model->id === $this->instance->getKey();
        });

        $resourceSyncListener = \Mockery::mock(\App\Listeners\V2\ResourceSync::class)->makePartial();
        $resourceSyncListener->handle(new \App\Events\V2\Instance\Saving($this->instance));

        $sync = Sync::where('resource_id', $this->instance->getKey())->first();

        $computeChangeListener = \Mockery::mock(\App\Listeners\V2\Instance\ComputeChange::class)->makePartial();
        $computeChangeListener->handle(new \App\Events\V2\Instance\Updated($this->instance));

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

        $backupMetric = BillingMetric::getActiveByKey($this->instance, 'backup.quota');
        $this->assertNull($backupMetric);
    }
}
