<?php
namespace Tests\unit\Listeners\V2\Instance;

use App\Models\V2\BillingMetric;
use App\Models\V2\ImageMetadata;
use App\Models\V2\Product;
use App\Models\V2\ProductPrice;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class UpdateLicenseBillingTest extends TestCase
{
    private Task $task;

    public function setUp(): void
    {
        parent::setUp();

        Model::withoutEvents(function() {
            $this->task = new Task([
                'id' => 'task-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->instance());
        });

        $mockAccountAdminClient = \Mockery::mock(\UKFast\Admin\Account\AdminClient::class);
        $mockAdminCustomerClient = \Mockery::mock(\UKFast\Admin\Account\AdminCustomerClient::class)->makePartial();
        $mockAdminCustomerClient->shouldReceive('getById')->andReturn(
            new \UKFast\Admin\Account\Entities\Customer(
                [
                    'accountStatus' => ''
                ]
            )
        );
        $mockAccountAdminClient->shouldReceive('customers')->andReturn(
            $mockAdminCustomerClient
        );
        app()->bind(\UKFast\Admin\Account\AdminClient::class, function () use ($mockAccountAdminClient) {
            return $mockAccountAdminClient;
        });
    }

    public function testInsertPleskLicenseBilling()
    {
        // Add a plesk billing product
        factory(Product::class)->create([
            'product_name' => $this->availabilityZone()->id . ': plesk-license',
            'product_category' => 'eCloud',
            'product_subcategory' => 'License',
        ])->each(function ($product) {
            factory(ProductPrice::class)->create([
                'product_price_product_id' => $product->id,
                'product_price_sale_price' => 0.00000816
            ]);
        });

        // add iimage metadata for ukfast.license.type = 'plesk'
        factory(ImageMetadata::class)->create([
            'key' => 'ukfast.license.type',
            'value' => 'plesk',
            'image_id' => $this->image()->id
        ]);

        $updateLicenseBillingListener = new \App\Listeners\V2\Instance\UpdateLicenseBilling();
        $updateLicenseBillingListener->handle(new \App\Events\V2\Task\Updated($this->task));

        $billingMetric = BillingMetric::getActiveByKey($this->instance(), 'license.plesk');

        $this->assertNotNull($billingMetric);
        $this->assertEquals(1, $billingMetric->value);
        $this->assertEquals(0.00000816, $billingMetric->price);
    }

    public function testNoLicenseTypeSkips()
    {
        $updateLicenseBillingListener = new \App\Listeners\V2\Instance\UpdateLicenseBilling();
        $updateLicenseBillingListener->handle(new \App\Events\V2\Task\Updated($this->task));

        $this->assertNull(BillingMetric::getActiveByKey($this->instance(), 'license.plesk'));
    }
}
