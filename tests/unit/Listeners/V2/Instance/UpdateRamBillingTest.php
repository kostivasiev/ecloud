<?php
namespace Tests\unit\Listeners\V2\Instance;

use App\Models\V2\BillingMetric;
use App\Models\V2\Product;
use App\Models\V2\ProductPrice;
use App\Models\V2\Sync;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateRamBillingTest extends TestCase
{
    use DatabaseMigrations;

    protected int $standardTier;

    protected Sync $sync;

    public function setUp(): void
    {
        parent::setUp();

        $this->standardTier = config('billing.ram_tiers.standard');

        $this->sync = Sync::withoutEvents(function() {
            $sync = new Sync([
                'id' => 'sync-1',
                'completed' => true,
                'type' => Sync::TYPE_UPDATE
            ]);
            $sync->resource()->associate($this->instance());
            return $sync;
        });

        factory(Product::class)->create([
            'product_name' => $this->availabilityZone()->id . ': ram-1mb',
        ])->each(function ($product) {
            factory(ProductPrice::class)->create([
                'product_price_product_id' => $product->id,
                'product_price_sale_price' => 0.00000816
            ]);
        });

        factory(Product::class)->create([
            'product_name' => $this->availabilityZone()->id . ': ram:high-1mb',
        ])->each(function ($product) {
            factory(ProductPrice::class)->create([
                'product_price_product_id' => $product->id,
                'product_price_sale_price' => 0.00000816
            ]);
        });
    }

    public function testInstanceCreatedStandardTierBilling()
    {
        $this->instance()->ram_capacity = 1024;

        $updateRamBillingListener = new \App\Listeners\V2\Instance\UpdateRamBilling();
        $updateRamBillingListener->handle(new \App\Events\V2\Sync\Updated($this->sync));

        $billingMetric = BillingMetric::getActiveByKey($this->instance(), 'ram.capacity');

        $this->assertNotNull($billingMetric);
        $this->assertEquals(1024, $billingMetric->value);
        $this->assertEquals(0.00000816, $billingMetric->price);
    }

    public function testInstanceCreatedHighTierBilling()
    {
        $this->instance()->ram_capacity = (config('billing.ram_tiers.standard') * 1024) + 1024;

        $updateRamBillingListener = new \App\Listeners\V2\Instance\UpdateRamBilling();
        $updateRamBillingListener->handle(new \App\Events\V2\Sync\Updated($this->sync));

        $standardRamMetric = BillingMetric::getActiveByKey($this->instance(), 'ram.capacity');
        $this->assertNotNull($standardRamMetric);
        $this->assertEquals(config('billing.ram_tiers.standard') * 1024, $standardRamMetric->value);
        $this->assertEquals(0.00000816, $standardRamMetric->price);

        $highRamMetric = BillingMetric::getActiveByKey($this->instance(), 'ram.capacity.high');
        $this->assertNotNull($highRamMetric);
        $this->assertEquals(1024, $highRamMetric->value);
        $this->assertEquals(0.00000816, $highRamMetric->price);
    }

    public function testInstanceResizedStandardTierBilling()
    {
        $originalRamMetric = factory(BillingMetric::class)->create([
            'id' => 'bm-test2',
            'resource_id' => $this->instance()->id,
            'vpc_id' => $this->vpc()->id,
            'key' => 'ram.capacity',
            'value' => 1024,
            'start' => '2020-07-07T10:30:00+01:00',
        ]);

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

        // Check existing metric was ended
        $originalRamMetric->refresh();
        $this->assertNotNull($originalRamMetric->end);

        $ramMetric = BillingMetric::getActiveByKey($this->instance(), 'ram.capacity');
        $this->assertNotNull($ramMetric);
        $this->assertEquals(2048, $ramMetric->value);
    }

    public function testInstanceResizedToHighTierBilling()
    {
        $originalRamMetric = factory(BillingMetric::class)->create([
            'id' => 'bm-test2',
            'resource_id' => $this->instance()->id,
            'vpc_id' => $this->vpc()->id,
            'key' => 'ram.capacity',
            'value' => 1024,
            'start' => '2020-07-07T10:30:00+01:00',
        ]);

        $this->instance()->ram_capacity = (config('billing.ram_tiers.standard') * 1024) + 1024;

        $updateRamBillingListener = new \App\Listeners\V2\Instance\UpdateRamBilling();
        $updateRamBillingListener->handle(new \App\Events\V2\Sync\Updated($this->sync));

        // Check existing metric was ended
        $originalRamMetric->refresh();
        $this->assertNotNull($originalRamMetric->end);

        // Check new metrics
        $standardRamMetric = BillingMetric::getActiveByKey($this->instance(), 'ram.capacity');
        $this->assertNotNull($standardRamMetric);
        $this->assertEquals(config('billing.ram_tiers.standard') * 1024, $standardRamMetric->value);
        $this->assertEquals(0.00000816, $standardRamMetric->price);

        $highRamMetric = BillingMetric::getActiveByKey($this->instance(), 'ram.capacity.high');
        $this->assertNotNull($highRamMetric);
        $this->assertEquals(1024, $highRamMetric->value);
        $this->assertEquals(0.00000816, $highRamMetric->price);
    }

    public function testInstanceResizedToStandardTierBillingFromHigh()
    {
        $originalStandardMetric = factory(BillingMetric::class)->create([
            'id' => 'bm-' . uniqid(),
            'resource_id' => $this->instance()->id,
            'vpc_id' => $this->vpc()->id,
            'key' => 'ram.capacity',
            'value' => config('billing.ram_tiers.standard') * 1024,
            'start' => '2020-07-07T10:30:00+01:00',
        ]);

        $originalHighMetric = factory(BillingMetric::class)->create([
            'id' => 'bm-' . uniqid(),
            'resource_id' => $this->instance()->id,
            'vpc_id' => $this->vpc()->id,
            'key' => 'ram.capacity',
            'value' => 1024,
            'start' => '2020-07-07T10:30:00+01:00',
        ]);

        $this->instance()->ram_capacity = 1024;

        $updateRamBillingListener = new \App\Listeners\V2\Instance\UpdateRamBilling();
        $updateRamBillingListener->handle(new \App\Events\V2\Sync\Updated($this->sync));

        // Check existing metrics were ended
        $originalStandardMetric->refresh();
        $originalHighMetric->refresh();

        $this->assertNotNull($originalStandardMetric->end);
        $this->assertNotNull($originalHighMetric->end);

        // Check new metrics
        $newMetric = BillingMetric::getActiveByKey($this->instance(), 'ram.capacity');
        $this->assertNotNull($newMetric);
        $this->assertEquals(1024, $newMetric->value);
        $this->assertEquals(0.00000816, $newMetric->price);

        $highMetric = BillingMetric::getActiveByKey($this->instance(), 'ram.capacity.high');
        $this->assertNull($highMetric);
    }
}
