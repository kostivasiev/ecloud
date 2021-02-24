<?php

namespace Tests\unit\Volumes;

use App\Events\V2\Sync\Updated;
use App\Listeners\V2\Volume\UpdateBilling;
use App\Models\V2\BillingMetric;
use App\Models\V2\Product;
use App\Models\V2\Volume;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class VolumeIopsBillingTest extends TestCase
{
    use DatabaseMigrations;

    public UpdateBilling $updateBilling;
    public BillingMetric $billingMetric;

    public function setUp(): void
    {
        parent::setUp();

        // Setup Products
        foreach ([300, 600, 1200, 2500] as $iops) {
            factory(Product::class)->create([
                'product_name' => $this->availabilityZone()->id.': volume@'.$iops.'-1gb',
                'product_category' => 'eCloud',
                'product_subcategory' => 'Storage',
                'product_supplier' => 'UKFast',
                'product_active' => 'Yes',
                'product_duration_type' => 'Hour'
            ]);
        }

        $this->billingMetric = app()->make(BillingMetric::class);
        app()->bind(BillingMetric::class, function () {
            return $this->billingMetric;
        });
        $this->updateBilling = new UpdateBilling();
    }

    public function testDefaultIopsBilling()
    {
        $volume = factory(Volume::class)->create([
            'id' => 'vol-abc123xyz',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
        ]);

        // Setup event and fire listener
        $event = new Updated($volume);
        $this->updateBilling->handle($event);

        // Update the billingMetric instance now it's been saved
        $this->billingMetric->refresh();
        $this->assertEquals(300, $volume->iops);
        $this->assertEquals('disk.capacity.300', $this->billingMetric->key);
        $this->assertNull($this->billingMetric->end);
    }

    public function testUnmountedVolumeWithNonDefaultIops()
    {
        $volume = factory(Volume::class)->create([
            'id' => 'vol-abc123xyz',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'iops' => 600,
        ]);

        // Setup event and fire listener
        $event = new Updated($volume);
        $this->updateBilling->handle($event);

        // Update the billingMetric instance now it's been saved
        $this->billingMetric->refresh();
        $this->assertEquals(300, $volume->iops);
        $this->assertEquals('disk.capacity.300', $this->billingMetric->key);
        $this->assertNull($this->billingMetric->end);
    }

}