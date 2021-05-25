<?php
namespace Tests\unit\Listeners\V2\Image;

use App\Events\V2\Task\Updated;
use App\Listeners\V2\Image\UpdateBilling;
use App\Models\V2\BillingMetric;
use App\Models\V2\Image;
use App\Models\V2\Product;
use App\Models\V2\ProductPrice;
use App\Models\V2\Task;
use App\Models\V2\Volume;
use App\Support\Sync;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class UpdateBillingTest extends TestCase
{
    protected $listener;
    protected Image $image;
    protected Task $task;
    protected Volume $volume;
    protected Product $product;
    protected ProductPrice $price;

    public function setUp(): void
    {
        parent::setUp();
        $this->listener = \Mockery::mock(UpdateBilling::class)->makePartial();

        // Setup the product
        $this->product = factory(Product::class)->create([
            'product_name' => $this->availabilityZone()->id . ': volume-1gb',
            'product_subcategory' => 'Storage',
        ]);
        $this->price = factory(ProductPrice::class)->create([
            'product_price_product_id' => $this->product->product_id,
            'product_price_sale_price' => 0.00008219,
        ]);

        // create volume and attach to an instance
        $this->volume = Model::withoutEvents(function () {
            return factory(Volume::class)->create([
                'id' => 'vol-test',
                'name' => 'Volume',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'capacity' => 20,
            ]);
        });
        $this->instance()->volumes()->sync([$this->volume->id]);
        $this->instance()->refresh();

        $this->image = Model::withoutEvents(function () {
            return factory(Image::class)->create([
                'id' => 'img-billingtest',
            ]);
        });
        $this->image->availabilityZones()->sync([$this->availabilityZone()->id]);
        $this->instance()->image_id = $this->image->id;
        $this->instance()->saveQuietly();
        $this->image->saveQuietly();

        // Setup the task
        $this->task = Model::withoutEvents(function () {
            $task = new Task([
                'id' => 'task-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($this->image);
            return $task;
        });
    }

    public function testStartBilling()
    {
        $event = new Updated($this->task);
        $this->listener->handle($event);

        // Get the billing metric
        $billingMetric = BillingMetric::where('resource_id', '=', $this->task->resource->id)->first();
        $this->assertEquals($this->product->product_subcategory, $billingMetric->category);
        $this->assertEquals($this->image->id, $billingMetric->resource_id);
        $this->assertEquals($this->price->product_price_sale_price, $billingMetric->price);
        $this->assertNull($billingMetric->end);
    }

    public function testEndBilling()
    {
        $billingMetric = factory(BillingMetric::class)->create([
            'resource_id' => $this->image->id,
            'vpc_id' => $this->vpc()->id,
            'reseller_id' => '1',
            'key' => 'private.image',
            'value' => '20',
            'start' => Carbon::now(),
            'category' => 'Storage',
            'price' => 0.00008219,
        ]);
        $event = new Updated($this->task);
        $this->listener->handle($event);

        $billingMetric->refresh();
        $this->assertNotNull($billingMetric->end);
    }

    public function testBillingWhenThereAreNoInstances()
    {
        $image = factory(Image::class)->create();
        $this->instance()->image_id = $image->id;
        $this->instance()->saveQuietly();

        $event = new Updated($this->task);
        $this->listener->handle($event);

        $billingMetric = BillingMetric::where('resource_id', '=', $this->task->resource->id)->first();
        $this->assertTrue(empty($billingMetric));
    }
}