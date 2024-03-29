<?php

namespace Tests\Unit\Listeners\V2\FloatingIp;

use App\Models\V2\BillingMetric;
use App\Models\V2\FloatingIp;
use App\Models\V2\Product;
use App\Models\V2\ProductPrice;
use App\Models\V2\Task;
use App\Support\Sync;
use Faker\Factory as Faker;
use Faker\Generator;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class UpdateBillingTest extends TestCase
{
    protected Task $task;

    protected FloatingIp $floatingIp;

    protected Generator $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        Model::withoutEvents(function () {
            $this->floatingIp = FloatingIp::factory()->create([
                'id' => 'fip-' . uniqid(),
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id
            ]);
            $this->task = new Task([
                'id' => 'sync-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->floatingIp);
        });

        Product::factory()->create([
            'product_name' => $this->availabilityZone()->id . ': floating ip',
        ])->each(function ($product) {
            ProductPrice::factory()->create([
                'product_price_product_id' => $product->id,
                'product_price_sale_price' => 0.006849315
            ]);
        });

        $mockAccountAdminClient = \Mockery::mock(\UKFast\Admin\Account\AdminClient::class);
        $mockAdminCustomerClient = \Mockery::mock(\UKFast\Admin\Account\AdminCustomerClient::class)->makePartial();
        $mockAdminCustomerClient->allows('getById')->andReturns(
            new \UKFast\Admin\Account\Entities\Customer(
                [
                    'accountStatus' => ''
                ]
            )
        );
        $mockAccountAdminClient->allows('customers')->andReturns(
            $mockAdminCustomerClient
        );
        app()->bind(\UKFast\Admin\Account\AdminClient::class, function () use ($mockAccountAdminClient) {
            return $mockAccountAdminClient;
        });
    }

    /**
     * @throws \Exception
     */
    public function testCreatingFloatingIpAddsBillingMetric()
    {
        $listener = new \App\Listeners\V2\FloatingIp\UpdateBilling();
        $listener->handle(new \App\Events\V2\Task\Updated($this->task));

        $metric = BillingMetric::getActiveByKey($this->floatingIp, 'floating-ip.count');
        $this->assertNotNull($metric);
        $this->assertEquals('floating-ip.count', $metric->key);
        $this->assertEquals(1, $metric->value);
    }
}
