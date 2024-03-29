<?php

namespace Tests\Unit\Listeners\V2\Instance;

use App\Models\V2\BillingMetric;
use App\Models\V2\Product;
use App\Models\V2\ProductPrice;
use App\Models\V2\Task;
use App\Services\V2\KingpinService;
use App\Support\Sync;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;

class UpdateRamBillingTest extends TestCase
{
    use LoadBalancerMock;

    protected int $standardTier;

    protected Task $task;

    public function setUp(): void
    {
        parent::setUp();

        $this->standardTier = config('billing.ram_tiers.standard');

        $this->task = Model::withoutEvents(function () {
            $task = new Task([
                'id' => 'task-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE
            ]);
            $task->resource()->associate($this->instanceModel());
            return $task;
        });

        Product::factory()->create([
            'product_name' => $this->availabilityZone()->id . ': ram-1mb',
        ])->each(function ($product) {
            ProductPrice::factory()->create([
                'product_price_product_id' => $product->id,
                'product_price_sale_price' => 0.00000816
            ]);
        });

        Product::factory()->create([
            'product_name' => $this->availabilityZone()->id . ': ram:high-1mb',
        ])->each(function ($product) {
            ProductPrice::factory()->create([
                'product_price_product_id' => $product->id,
                'product_price_sale_price' => 0.00000816
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

        $this->kingpinServiceMock()->allows('get')
            ->andReturn(
                new Response(200, [], json_encode([
                    'powerState' => KingpinService::INSTANCE_POWERSTATE_POWEREDON,
                    'toolsRunningStatus' => KingpinService::INSTANCE_TOOLSRUNNINGSTATUS_RUNNING,
                ]))
            );
    }

    public function testInstanceCreatedStandardTierBilling()
    {
        $this->instanceModel()->ram_capacity = 1024;

        $updateRamBillingListener = new \App\Listeners\V2\Instance\UpdateRamBilling();
        $updateRamBillingListener->handle(new \App\Events\V2\Task\Updated($this->task));

        $billingMetric = BillingMetric::getActiveByKey($this->instanceModel(), 'ram.capacity');

        $this->assertNotNull($billingMetric);
        $this->assertEquals(1024, $billingMetric->value);
        $this->assertEquals(0.00000816, $billingMetric->price);
    }

    public function testInstanceCreatedHighTierBilling()
    {
        $this->instanceModel()->ram_capacity = (config('billing.ram_tiers.standard') * 1024) + 1024;

        $updateRamBillingListener = new \App\Listeners\V2\Instance\UpdateRamBilling();
        $updateRamBillingListener->handle(new \App\Events\V2\Task\Updated($this->task));

        $standardRamMetric = BillingMetric::getActiveByKey($this->instanceModel(), 'ram.capacity');
        $this->assertNotNull($standardRamMetric);
        $this->assertEquals(config('billing.ram_tiers.standard') * 1024, $standardRamMetric->value);
        $this->assertEquals(0.00000816, $standardRamMetric->price);

        $highRamMetric = BillingMetric::getActiveByKey($this->instanceModel(), 'ram.capacity.high');
        $this->assertNotNull($highRamMetric);
        $this->assertEquals(1024, $highRamMetric->value);
        $this->assertEquals(0.00000816, $highRamMetric->price);
    }

    public function testInstanceResizedStandardTierBilling()
    {
        $originalRamMetric = BillingMetric::factory()->create([
            'id' => 'bm-test2',
            'resource_id' => $this->instanceModel()->id,
            'vpc_id' => $this->vpc()->id,
            'key' => 'ram.capacity',
            'value' => 1024,
            'start' => '2020-07-07T10:30:00+01:00',
        ]);

        $this->instanceModel()->ram_capacity = 2048;

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE
            ]);
            $this->task->resource()->associate($this->instanceModel());
        });

        // Check that the ram billing metric is added
        $updateRamBillingListener = new \App\Listeners\V2\Instance\UpdateRamBilling();
        $updateRamBillingListener->handle(new \App\Events\V2\Task\Updated($this->task));

        // Check existing metric was ended
        $originalRamMetric->refresh();
        $this->assertNotNull($originalRamMetric->end);

        $ramMetric = BillingMetric::getActiveByKey($this->instanceModel(), 'ram.capacity');
        $this->assertNotNull($ramMetric);
        $this->assertEquals(2048, $ramMetric->value);
    }

    public function testInstanceResizedToHighTierBilling()
    {
        $originalRamMetric = BillingMetric::factory()->create([
            'id' => 'bm-test2',
            'resource_id' => $this->instanceModel()->id,
            'vpc_id' => $this->vpc()->id,
            'key' => 'ram.capacity',
            'value' => 1024,
            'start' => '2020-07-07T10:30:00+01:00',
        ]);

        $this->instanceModel()->ram_capacity = (config('billing.ram_tiers.standard') * 1024) + 1024;

        $updateRamBillingListener = new \App\Listeners\V2\Instance\UpdateRamBilling();
        $updateRamBillingListener->handle(new \App\Events\V2\Task\Updated($this->task));

        // Check existing metric was ended
        $originalRamMetric->refresh();
        $this->assertNotNull($originalRamMetric->end);

        // Check new metrics
        $standardRamMetric = BillingMetric::getActiveByKey($this->instanceModel(), 'ram.capacity');
        $this->assertNotNull($standardRamMetric);
        $this->assertEquals(config('billing.ram_tiers.standard') * 1024, $standardRamMetric->value);
        $this->assertEquals(0.00000816, $standardRamMetric->price);

        $highRamMetric = BillingMetric::getActiveByKey($this->instanceModel(), 'ram.capacity.high');
        $this->assertNotNull($highRamMetric);
        $this->assertEquals(1024, $highRamMetric->value);
        $this->assertEquals(0.00000816, $highRamMetric->price);
    }

    public function testInstanceResizedToStandardTierBillingFromHigh()
    {
        $originalStandardMetric = BillingMetric::factory()->create([
            'id' => 'bm-' . uniqid(),
            'resource_id' => $this->instanceModel()->id,
            'vpc_id' => $this->vpc()->id,
            'key' => 'ram.capacity',
            'value' => config('billing.ram_tiers.standard') * 1024,
            'start' => '2020-07-07T10:30:00+01:00',
        ]);

        $originalHighMetric = BillingMetric::factory()->create([
            'id' => 'bm-' . uniqid(),
            'resource_id' => $this->instanceModel()->id,
            'vpc_id' => $this->vpc()->id,
            'key' => 'ram.capacity',
            'value' => 1024,
            'start' => '2020-07-07T10:30:00+01:00',
        ]);

        $this->instanceModel()->ram_capacity = 1024;

        $updateRamBillingListener = new \App\Listeners\V2\Instance\UpdateRamBilling();
        $updateRamBillingListener->handle(new \App\Events\V2\Task\Updated($this->task));

        // Check existing metrics were ended
        $originalStandardMetric->refresh();
        $originalHighMetric->refresh();

        $this->assertNotNull($originalStandardMetric->end);
        $this->assertNotNull($originalHighMetric->end);

        // Check new metrics
        $newMetric = BillingMetric::getActiveByKey($this->instanceModel(), 'ram.capacity');
        $this->assertNotNull($newMetric);
        $this->assertEquals(1024, $newMetric->value);
        $this->assertEquals(0.00000816, $newMetric->price);

        $highMetric = BillingMetric::getActiveByKey($this->instanceModel(), 'ram.capacity.high');
        $this->assertNull($highMetric);
    }

    public function testLoadBalancerInstancesIgnored()
    {
        $this->instanceModel()->ram_capacity = 1024;

        $this->instanceModel()->loadBalancer()->associate($this->loadBalancer())->save();

        $updateRamBillingListener = new \App\Listeners\V2\Instance\UpdateRamBilling();
        $updateRamBillingListener->handle(new \App\Events\V2\Task\Updated($this->task));

        $billingMetric = BillingMetric::getActiveByKey($this->instanceModel(), 'ram.capacity');

        $this->assertNull($billingMetric);
    }
}
