<?php
namespace Tests\unit\Listeners\V2\Instance;

use App\Listeners\V2\Instance\ComputeChange;
use App\Models\V2\BillingMetric;
use App\Models\V2\Sync;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateRamBillingTest extends TestCase
{
    use DatabaseMigrations;

    private $sync;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testRamChangeBilling()
    {
        $originalRamMetric = factory(BillingMetric::class)->create([
            'id' => 'bm-test2',
            'resource_id' => $this->instance()->id,
            'vpc_id' => $this->vpc()->id,
            'key' => 'ram.capacity',
            'value' => 1024,
            'start' => '2020-07-07T10:30:00+01:00',
        ]);

        // Update the instance compute values
        $this->instance()->ram_capacity = 2048;

        Sync::withoutEvents(function() {
            $this->sync = new Sync([
                'id' => 'sync-1',
                'completed' => true,
                'type' => Sync::TYPE_UPDATE
            ]);
            $this->sync->resource()->associate($this->instance());
        });

        // Check that the ram billing metric is added
        $updateRamBillingListener = new \App\Listeners\V2\Instance\UpdateRamBilling();
        $updateRamBillingListener->handle(new \App\Events\V2\Sync\Updated($this->sync));

        $ramMetric = BillingMetric::getActiveByKey($this->instance(), 'ram.capacity');
        $this->assertNotNull($ramMetric);
        $this->assertEquals(2048, $ramMetric->value);

        // Check existing metric was ended
        $originalRamMetric->refresh();

        $this->assertNotNull($originalRamMetric->end);
    }
}
