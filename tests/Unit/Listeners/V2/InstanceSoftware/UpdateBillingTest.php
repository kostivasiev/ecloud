<?php

namespace Tests\Unit\Listeners\V2\InstanceSoftware;

use App\Models\V2\BillingMetric;
use App\Models\V2\InstanceSoftware;
use App\Models\V2\Product;
use App\Models\V2\ProductPrice;
use App\Models\V2\Software;
use App\Models\V2\Task;
use App\Support\Sync;
use Database\Seeders\SoftwareSeeder;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class UpdateBillingTest extends TestCase
{
    public $software;

    public $instanceSoftware;

    public function setUp(): void
    {
        parent::setUp();
        (new SoftwareSeeder())->run();

        $this->software = Software::find('soft-aaaaaaaa');

        $this->instanceSoftware = InstanceSoftware::factory()->make([
            'name' => $this->software->name
        ]);
        $this->instanceSoftware->instance()->associate($this->instanceModel());
        $this->instanceSoftware->software()->associate($this->software);
        $this->instanceSoftware->save();

        // Create billing product for az-test: software:test software
        $this->product = Product::factory()->create([
            'product_sales_product_id' => 0,
            'product_name' => $this->availabilityZone()->id.': software:test software',
            'product_category' => 'eCloud',
            'product_subcategory' => 'License',
            'product_supplier' => 'UKFast',
            'product_active' => 'Yes',
            'product_duration_type' => 'Hour',
            'product_duration_length' => 1,
        ]);
        ProductPrice::factory()->create([
            'product_price_product_id' => $this->product->id,
            'product_price_sale_price' => 0.0000115314,
        ]);

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

    public function testBillingWithLicenseAndBillingProductAddsMetric()
    {
        $task = Model::withoutEvents(function() {
            $task = new Task([
                'id' => 'sync-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($this->instanceSoftware);
            return $task;
        });

        // Check that the billing metric is added
        $updateBillingListener = new \App\Listeners\V2\InstanceSoftware\UpdateBilling;
        $updateBillingListener->handle(new \App\Events\V2\Task\Updated($task));

        $billingMetric = BillingMetric::getActiveByKey($this->instanceModel(), 'software.test-software');

        $this->assertNotNull($billingMetric);
        $this->assertEquals($this->instanceModel()->id, $billingMetric->resource_id);
        $this->assertEquals($this->vpc()->id, $billingMetric->vpc_id);
        $this->assertEquals($this->vpc()->reseller_id, $billingMetric->reseller_id);
        $this->assertEquals('Software: Test Software', $billingMetric->name);
        $this->assertEquals('software.test-software', $billingMetric->key);
        $this->assertEquals(1, $billingMetric->value);
    }

    public function testBillingWithoutLicenseAddsNoMetric()
    {
        $this->software->setAttribute('license', null)->save();

        $task = Model::withoutEvents(function() {
            $task = new Task([
                'id' => 'sync-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($this->instanceSoftware);
            return $task;
        });

        $updateBillingListener = new \App\Listeners\V2\InstanceSoftware\UpdateBilling;
        $updateBillingListener->handle(new \App\Events\V2\Task\Updated($task));

        $this->assertCount(0, BillingMetric::all());
    }
}
