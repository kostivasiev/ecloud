<?php

namespace Tests\Unit\Listeners\V2\Instance;

use App\Models\V2\BillingMetric;
use App\Models\V2\Task;
use App\Models\V2\Volume;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;

class UpdateBackupBillingTest extends TestCase
{
    use LoadBalancerMock;

    private $volume;
    private $task;

    public function setUp(): void
    {
        parent::setUp();

        Volume::withoutEvents(function () {
            $this->volume = Volume::factory()->create([
                'id' => 'vol-test',
                'vpc_id' => $this->vpc()->id,
                'capacity' => 20,
                'availability_zone_id' => $this->availabilityZone()->id,
            ]);

            $this->volume->instances()->attach($this->instanceModel());
        });
    }

    public function testEnableBackupUpdatesBillingMetrics()
    {
        $this->assertNull(BillingMetric::getActiveByKey($this->instanceModel(), 'backup.quota'));

        // Update the instance compute values
        $this->instanceModel()->backup_enabled = true;

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'task-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->instanceModel());
        });

        // Check that the vcpu billing metric is added
        $updateBackupBillingListener = new \App\Listeners\V2\Instance\UpdateBackupBilling();
        $updateBackupBillingListener->handle(new \App\Events\V2\Task\Updated($this->task));

        $backupMetric = BillingMetric::getActiveByKey($this->instanceModel(), 'backup.quota');

        $this->assertNotNull($backupMetric);

        $this->assertEquals($this->volume->capacity, $backupMetric->value);
    }

    public function testDisableBackupUpdatesBillingMetrics()
    {
        $billingMetric = BillingMetric::factory()->create([
            'resource_id' => $this->instanceModel()->id,
            'vpc_id' => $this->vpc()->id,
            'key' => 'backup.quota',
            'value' => $this->volume->capacity
        ]);

        $this->assertNotNull(BillingMetric::getActiveByKey($this->instanceModel(), 'backup.quota'));

        // Update the instance compute values
        $this->instanceModel()->backup_enabled = false;

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'task-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->instanceModel());
        });

        // Check that the vcpu billing metric is added
        $updateBackupBillingListener = new \App\Listeners\V2\Instance\UpdateBackupBilling();
        $updateBackupBillingListener->handle(new \App\Events\V2\Task\Updated($this->task));

        $billingMetric->refresh();
        $this->assertNotNull($billingMetric->end);

        $backupMetric = BillingMetric::getActiveByKey($this->instanceModel(), 'backup.quota');
        $this->assertNull($backupMetric);
    }

    public function testResizingVolumeUpdatesBackupBillingMetric()
    {
        Model::withoutEvents(function () {
            $this->instanceModel()->backup_enabled = true;
            $this->instanceModel()->save();

            $this->volume->capacity = 15;
            $this->volume->save();

            $this->task = new Task([
                'id' => 'task-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->volume);
        });

        // Check that the backup billing metric is added
        $updateBackupBillingListener = new \App\Listeners\V2\Instance\UpdateBackupBilling();
        $updateBackupBillingListener->handle(new \App\Events\V2\Task\Updated($this->task));

        $backupMetric = BillingMetric::getActiveByKey($this->instanceModel(), 'backup.quota');

        $this->assertNotNull($backupMetric);

        $this->assertEquals(15, $backupMetric->value);
    }

    public function testResizingVolumeEndsExistingBillingMetric()
    {
        $metric = BillingMetric::factory()->create([
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

            $this->task = new Task([
                'id' => 'task-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->volume);
        });

        $dispatchResourceSyncedEventListener = new \App\Listeners\V2\Volume\UpdateBilling();
        $dispatchResourceSyncedEventListener->handle(new \App\Events\V2\Task\Updated($this->task));

        $this->assertEquals(2, BillingMetric::where('resource_id', $this->volume->id)->count());

        $metric->refresh();

        $this->assertNotNull($metric->end);
    }

    public function testLoadBalancerInstancesIgnored()
    {
        $this->instanceModel()->setAttribute('backup_enabled', true)->save();

        $this->volume->capacity = 15;
        $this->volume->save();

        $this->instanceModel()->loadBalancer()->associate($this->loadBalancer())->save();

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'task-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->volume);
        });

        // Check that the backup billing metric is not added
        $updateBackupBillingListener = new \App\Listeners\V2\Instance\UpdateBackupBilling();
        $updateBackupBillingListener->handle(new \App\Events\V2\Task\Updated($this->task));

        $backupMetric = BillingMetric::getActiveByKey($this->instanceModel(), 'backup.quota');

        $this->assertNull($backupMetric);
    }
}
