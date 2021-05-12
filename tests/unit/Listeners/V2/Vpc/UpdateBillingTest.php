<?php
namespace Tests\unit\Listeners\V2\Vpc;

use App\Models\V2\BillingMetric;
use App\Models\V2\Product;
use App\Models\V2\ProductPrice;
use App\Models\V2\Task;
use App\Models\V2\Vpc;
use App\Support\Sync;
use Faker\Factory as Faker;
use Faker\Generator;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class UpdateBillingTest extends TestCase
{
    protected Task $task;
    protected Vpc $vpc;
    protected Generator $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        Model::withoutEvents(function () {
            $this->vpc = factory(Vpc::class)->create([
                'id' => 'vpc-' . uniqid(),
                'region_id' => $this->region()->id,
                'advanced_networking' => true,
            ]);
        });

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->vpc);
        });

        factory(Product::class)->create([
            'product_name' => $this->availabilityZone()->id . ': advanced networking',
        ])->each(function ($product) {
            factory(ProductPrice::class)->create([
                'product_price_product_id' => $product->id,
                'product_price_sale_price' => 0.001388889,
            ]);
        });
    }

    public function testCreateVpcAdvancedNetworkingBillingMetric()
    {
        $listener = new \App\Listeners\V2\Vpc\UpdateBilling();
        $listener->handle(new \App\Events\V2\Task\Updated($this->task));

        $metric = BillingMetric::getActiveByKey($this->vpc, 'networking.advanced');
        $this->assertNotNull($metric);
        $this->assertEquals('networking.advanced', $metric->key);
        $this->assertEquals(1, $metric->value);
    }

}
