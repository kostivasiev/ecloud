<?php
namespace Tests\unit\Listeners\V2\Vpc;

use App\Models\V2\BillingMetric;
use App\Models\V2\FloatingIp;
use App\Models\V2\Product;
use App\Models\V2\ProductPrice;
use App\Models\V2\Sync;
use Faker\Factory as Faker;
use Faker\Generator;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateFloatingIpBillingTest extends TestCase
{
    use DatabaseMigrations;

    protected Sync $sync;

    protected FloatingIp $floatingIp;

    protected Generator $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        Model::withoutEvents(function () {
            $this->floatingIp = factory(FloatingIp::class)->create([
                'id' => 'fip-' . uniqid(),
                'vpc_id' => $this->vpc()->id
            ]);
        });

        Sync::withoutEvents(function() {
            $this->sync = new Sync([
                'id' => 'sync-1',
                'completed' => true,
                'type' => Sync::TYPE_UPDATE
            ]);
            $this->sync->resource()->associate($this->floatingIp);
        });

        factory(Product::class)->create([
            'product_name' => $this->availabilityZone()->id . ': floating ip',
        ])->each(function ($product) {
            factory(ProductPrice::class)->create([
                'product_price_product_id' => $product->id,
                'product_price_sale_price' => 0.006849315
            ]);
        });
    }

    public function testCreatingFloatingIpAddsBillingMetric()
    {
        $listener = new \App\Listeners\V2\Vpc\UpdateFloatingIpBilling();
        $listener->handle(new \App\Events\V2\Sync\Updated($this->sync));

        $metric = BillingMetric::getActiveByKey($this->vpc(), 'floating-ip.count');
        $this->assertNotNull($metric);
        $this->assertEquals('floating-ip.count', $metric->key);
        $this->assertEquals(1, $metric->value);
    }

    public function testAddingNewFloatingIpIncrementsBillingMetric()
    {
        Model::withoutEvents(function () {
            factory(FloatingIp::class)->create([
                'id' => 'fip-' . uniqid(),
                'vpc_id' => $this->vpc()->id,
                'ip_address' => '2.2.2.2'
            ]);
        });

        $originalMetric = factory(BillingMetric::class)->create([
            'id' => 'bm-' . uniqid(),
            'resource_id' => $this->vpc()->id,
            'vpc_id' => $this->vpc()->id,
            'key' => 'floating-ip.count',
            'value' => 1,
            'start' => '2020-07-07T10:30:00+01:00',
        ]);

        $listener = new \App\Listeners\V2\Vpc\UpdateFloatingIpBilling();
        $listener->handle(new \App\Events\V2\Sync\Updated($this->sync));

        // Check existing metric was ended
        $originalMetric->refresh();
        $this->assertNotNull($originalMetric->end);

        $activeMetric = BillingMetric::getActiveByKey($this->vpc(), 'floating-ip.count');
        $this->assertNotNull($activeMetric);
        $this->assertEquals(2, $activeMetric->value);
    }

    public function testDeletingFloatingIpEndsBillingMetric()
    {
        for ($i = 0; $i < 4; $i++) {
            Model::withoutEvents(function () {
                factory(FloatingIp::class)->create([
                    'id' => 'fip-' . uniqid(),
                    'vpc_id' => $this->vpc()->id,
                    'ip_address' => $this->faker->ipv4
                ]);
            });
        }

        $this->assertEquals(5, FloatingIp::all()->count());

        $originalMetric = factory(BillingMetric::class)->create([
            'id' => 'bm-' . uniqid(),
            'resource_id' => $this->vpc()->id,
            'vpc_id' => $this->vpc()->id,
            'key' => 'floating-ip.count',
            'value' => 5,
            'start' => '2020-07-07T10:30:00+01:00',
        ]);

        $sync = Sync::withoutEvents(function() {
            $sync = new Sync([
                'id' => 'sync-' . uniqid(),
                'completed' => true,
                'type' => Sync::TYPE_DELETE
            ]);
            $sync->resource()->associate($this->floatingIp);
            return $sync;
        });

        $listener = new \App\Listeners\V2\Vpc\UpdateFloatingIpBilling();
        $listener->handle(new \App\Events\V2\Sync\Updated($sync));

        // Check existing metric was ended
        $originalMetric->refresh();
        $this->assertNotNull($originalMetric->end);

        $activeMetric = BillingMetric::getActiveByKey($this->vpc(), 'floating-ip.count');
        $this->assertNotNull($activeMetric);
        $this->assertEquals(4, $activeMetric->value);
    }
}
