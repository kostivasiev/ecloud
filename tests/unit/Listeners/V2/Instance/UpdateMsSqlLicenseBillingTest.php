<?php

namespace Tests\unit\Listeners\V2\Instance;

use App\Events\V2\Task\Updated;
use App\Listeners\V2\Instance\UpdateMsSqlLicenseBilling;
use App\Models\V2\BillingMetric;
use App\Models\V2\ImageMetadata;
use App\Models\V2\Product;
use App\Models\V2\ProductPrice;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Mockery;
use Tests\TestCase;

class UpdateMsSqlLicenseBillingTest extends TestCase
{
    public $billingJobMock;
    public Task $task;

    public function setUp(): void
    {
        parent::setUp();
        $this->billingJobMock = Mockery::mock(UpdateMsSqlLicenseBilling::class)->makePartial();

        factory(Product::class)->create([
            'product_name' => $this->availabilityZone()->id . ': mssql standard license',
        ])->each(function ($product) {
            factory(ProductPrice::class)->create([
                'product_price_product_id' => $product->id,
                'product_price_sale_price' => 1
            ]);
        });

        $mockAccountAdminClient = \Mockery::mock(\UKFast\Admin\Account\AdminClient::class);
        $mockAdminCustomerClient = \Mockery::mock(\UKFast\Admin\Account\AdminCustomerClient::class)->makePartial();
        $mockAdminCustomerClient->allows('getById')
            ->andReturns(
                new \UKFast\Admin\Account\Entities\Customer(
                    [
                        'accountStatus' => ''
                    ]
                )
            );
        $mockAccountAdminClient->allows('customers')
            ->andReturns(
                $mockAdminCustomerClient
            );
        app()->bind(\UKFast\Admin\Account\AdminClient::class, function () use ($mockAccountAdminClient) {
            return $mockAccountAdminClient;
        });

        $mockAdminLicensesClient = \Mockery::mock(\UKFast\Admin\Licenses\AdminClient::class);
        $mockAdminLicensesClient->allows('setResellerId')->andReturns($mockAdminLicensesClient);
        $mockAdminLicensesClient->allows('licenses')->andReturnUsing(function () {
            $licenseMock = \Mockery::mock(\UKFast\Admin\Licenses\AdminLicensesClient::class)->makePartial();
            $licenseMock->allows()->getAll([
                'owner_id:eq' => $this->instance()->id,
                'license_type:eq' => 'mssql',
            ])->andReturnUsing(function () {
                return [
                    new \UKFast\SDK\Licenses\Entities\License([
                        'id' => 'lic-abc123xyz',
                        'owner_id' => $this->instance()->id,
                        'owner_type' => 'ecloud',
                        'key_id' => 'WINDOWS-2019-DATACENTER-MSSQL2019-STANDARD',
                        'license_type' => 'mssql',
                        'reseller_id' => $this->instance()->getResellerId(),
                    ]),
                ];
            });
            return $licenseMock;
        });

        app()->bind(\UKFast\Admin\Licenses\AdminClient::class, function () use ($mockAdminLicensesClient) {
            return $mockAdminLicensesClient;
        });

        $this->image()->imageMetadata()->save(factory(ImageMetadata::class)->create([
            'key' => 'ukfast.license.type',
            'value' => 'mssql',
            'image_id' => $this->image()->id,
        ]));
        $this->image()->imageMetadata()->save(factory(ImageMetadata::class)->create([
            'key' => 'ukfast.license.mssql.edition',
            'value' => 'datacenter-mssql2019-standard',
            'image_id' => $this->image()->id,
        ]));
    }

    public function testInitialBillingMetricMinimum4Cores()
    {
        $vcpuCores = 2;
        $this->instance()->setAttribute('vcpu_cores', $vcpuCores)->saveQuietly();

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE
            ]);
            $this->task->resource()->associate($this->instance());
        });

        $event = new Updated($this->task);
        $this->billingJobMock->handle($event);

        $metric = $this->instance()->billingMetrics->first();
        $this->assertEquals(2, $metric->value); // packs
        $this->assertEquals('license.mssql.standard', $metric->key);
        $this->assertNull($metric->end);
    }

    public function testInitialBillingMetricWith5Cores()
    {
        $vcpuCores = 5; // should result in 3 packs
        $this->instance()->setAttribute('vcpu_cores', $vcpuCores)->saveQuietly();

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE
            ]);
            $this->task->resource()->associate($this->instance());
        });

        $event = new Updated($this->task);
        $this->billingJobMock->handle($event);

        $metric = $this->instance()->billingMetrics->first();
        $this->assertEquals(3, $metric->value); // packs
        $this->assertEquals('license.mssql.standard', $metric->key);
        $this->assertNull($metric->end);
    }

    public function testUpdateCoresEndsMetricStartsNewMetric()
    {
        $originalMetric = factory(BillingMetric::class)->create([
            "id" => "bm-orig",
            "resource_id" => $this->instance()->id,
            "vpc_id" => $this->vpc()->id,
            "reseller_id" => "1",
            "key" => "license.mssql.standard",
            "value" => "2",
            "start" => "2021-11-24 08:10:01",
            "end" => null,
            "created_at" => "2021-11-24 08:10:01",
            "updated_at" => "2021-11-24 08:10:01",
            "deleted_at" => null,
            "category" => "Compute",
            "price" => "2",
        ]);

        $vcpuCores = 5; // should result in 3 packs
        $this->instance()->setAttribute('vcpu_cores', $vcpuCores)->saveQuietly();

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE
            ]);
            $this->task->resource()->associate($this->instance());
        });

        $event = new Updated($this->task);
        $this->billingJobMock->handle($event);

        $originalMetric->refresh();
        $newMetric = $this->instance()->billingMetrics()->whereNull('end')->first();
        $this->assertEquals($originalMetric->resource_id, $newMetric->resource_id);
        $this->assertNotNull($originalMetric->end);
        $this->assertNull($newMetric->end);
        $this->assertEquals(3, $newMetric->value); // packs
        $this->assertEquals(3, $newMetric->price); // packs
        $this->assertEquals('license.mssql.standard', $newMetric->key);
        $this->assertNull($newMetric->end);
    }

}
