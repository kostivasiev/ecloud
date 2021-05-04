<?php
namespace Tests\unit\Listeners\V2\FloatingIp;

use App\Models\V2\BillingMetric;
use App\Models\V2\FloatingIp;
use App\Models\V2\Product;
use App\Models\V2\ProductPrice;
use App\Models\V2\Task;
use App\Support\Sync;
use Faker\Factory as Faker;
use Faker\Generator;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateBillingTest extends TestCase
{
    use DatabaseMigrations;

    protected Task $task;

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

        Model::withoutEvents(function() {
            $this->task = new Task([
                'id' => 'sync-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->floatingIp);
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
        $listener = new \App\Listeners\V2\FloatingIp\UpdateBilling();
        $listener->handle(new \App\Events\V2\Sync\Updated($this->task));

        $metric = BillingMetric::getActiveByKey($this->floatingIp, 'floating-ip.count');
        $this->assertNotNull($metric);
        $this->assertEquals('floating-ip.count', $metric->key);
        $this->assertEquals(1, $metric->value);
    }
}
