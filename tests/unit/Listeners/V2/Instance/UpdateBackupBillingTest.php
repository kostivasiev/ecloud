<?php
namespace Tests\unit\Listeners\V2\Instance;

use App\Models\V2\BillingMetric;
use App\Models\V2\Sync;
use App\Models\V2\Volume;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateBackupBillingTest extends TestCase
{
    private $volume;
    private $sync;

    public function setUp(): void
    {
        parent::setUp();

        Volume::withoutEvents(function () {
            $this->volume = factory(Volume::class)->create([
                'id' => 'vol-test',
                'vpc_id' => $this->vpc()->id,
                'capacity' => 20,
                'availability_zone_id' => $this->availabilityZone()->id,
            ]);

            $this->volume->instances()->attach($this->instance());
        });
    }

    public function testEnableBackupUpdatesBillingMetrics()
    {
        $this->assertNull(BillingMetric::getActiveByKey($this->instance(), 'backup.quota'));

        // Update the instance compute values
        $this->instance()->backup_enabled = true;

        Sync::withoutEvents(function () {
            $this->sync = new Sync([
                'id' => 'sync-1',
                'completed' => true,
                'type' => Sync::TYPE_UPDATE
            ]);
            $this->sync->resource()->associate($this->instance());
        });

        // Check that the vcpu billing metric is added
        $updateBackupBillingListener = new \App\Listeners\V2\Instance\UpdateBackupBilling();
        $updateBackupBillingListener->handle(new \App\Events\V2\Sync\Updated($this->sync));

        $backupMetric = BillingMetric::getActiveByKey($this->instance(), 'backup.quota');

        $this->assertNotNull($backupMetric);

        $this->assertEquals($this->volume->capacity, $backupMetric->value);
    }

    public function testDisableBackupUpdatesBillingMetrics()
    {
        $billingMetric = factory(BillingMetric::class)->create([
            'resource_id' => $this->instance()->id,
            'vpc_id' => $this->vpc()->id,
            'key' => 'backup.quota',
            'value' => $this->volume->capacity
        ]);

        $this->assertNotNull(BillingMetric::getActiveByKey($this->instance(), 'backup.quota'));

        // Update the instance compute values
        $this->instance()->backup_enabled = false;

        Sync::withoutEvents(function () {
            $this->sync = new Sync([
                'id' => 'sync-1',
                'completed' => true,
                'type' => Sync::TYPE_UPDATE
            ]);
            $this->sync->resource()->associate($this->instance());
        });

        // Check that the vcpu billing metric is added
        $updateBackupBillingListener = new \App\Listeners\V2\Instance\UpdateBackupBilling();
        $updateBackupBillingListener->handle(new \App\Events\V2\Sync\Updated($this->sync));

        $billingMetric->refresh();
        $this->assertNotNull($billingMetric->end);

        $backupMetric = BillingMetric::getActiveByKey($this->instance(), 'backup.quota');
        $this->assertNull($backupMetric);
    }

    public function testResizingVolumeUpdatesBackupBillingMetric()
    {
        Model::withoutEvents(function () {
            $this->instance()->backup_enabled = true;
            $this->instance()->save();

            $this->volume->capacity = 15;
            $this->volume->save();

            $this->sync = new Sync([
                'id' => 'sync-1',
                'completed' => true,
                'type' => Sync::TYPE_UPDATE
            ]);
            $this->sync->resource()->associate($this->volume);
        });

        // Check that the backup billing metric is added
        $updateBackupBillingListener = new \App\Listeners\V2\Instance\UpdateBackupBilling();
        $updateBackupBillingListener->handle(new \App\Events\V2\Sync\Updated($this->sync));

        $backupMetric = BillingMetric::getActiveByKey($this->instance(), 'backup.quota');

        $this->assertNotNull($backupMetric);

        $this->assertEquals(15, $backupMetric->value);
    }

    public function testResizingVolumeEndsExistingBillingMetric()
    {
        $metric = factory(BillingMetric::class)->create([
            'resource_id' => $this->volume->id,
            'vpc_id' => $this->vpc()->id,
            'key' => 'disk.capacity.300',
            'value' => 10,
            'start' => '2020-07-07T10:30:00+01:00',
        ]);

        $this->assertEquals(1, BillingMetric::where('resource_id', $this->volume->id)->count());
        $this->assertNull($metric->end);

        Model::withoutEvents(function () {
            $this->volume->capacity = 15;
            $this->volume->iops = 600;
            $this->volume->save();

            $this->sync = new Sync([
                'id' => 'sync-1',
                'completed' => true,
                'type' => Sync::TYPE_UPDATE
            ]);
            $this->sync->resource()->associate($this->volume);
        });

        $dispatchResourceSyncedEventListener = new \App\Listeners\V2\Volume\UpdateBilling();
        $dispatchResourceSyncedEventListener->handle(new \App\Events\V2\Sync\Updated($this->sync));

        $this->assertEquals(2, BillingMetric::where('resource_id', $this->volume->id)->count());

        $metric->refresh();

        $this->assertNotNull($metric->end);
    }
}
