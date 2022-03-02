<?php

namespace Tests\unit\Console\Commands\Billing;

use App\Console\Commands\Billing\FixPriceOnAdvancedNetworkingBillingMetrics;
use App\Models\V2\BillingMetric;
use App\Models\V2\Product;
use App\Models\V2\ProductPrice;
use Tests\TestCase;

class FixPriceOnAdvancedNetworkingBillingMetricsTest extends TestCase
{
    public BillingMetric $metric;
    public $job;

    protected function setUp(): void
    {
        parent::setUp();
        $this->vpc()->setAttribute('advanced_networking', true)->saveQuietly();
        $this->metric = BillingMetric::factory()->create([
            'id' => 'bm-test',
            'resource_id' => $this->vpc()->id,
            'vpc_id' => $this->vpc()->id,
            'key' => 'networking.advanced',
            'value' => 0,
            'start' => '2020-07-07T10:30:00+01:00',
        ]);
        $this->job = \Mockery::mock(FixPriceOnAdvancedNetworkingBillingMetrics::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $this->job->allows('info')->withAnyArgs()->andReturnTrue();
        $this->job->allows('option')->withAnyArgs()->andReturnFalse();

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

        Product::factory()->create([
            'product_name' => $this->availabilityZone()->id . ': advanced networking',
            'product_subcategory' => 'Networking',
        ])->each(function ($product) {
            ProductPrice::factory()->create([
                'product_price_product_id' => $product->id,
                'product_price_sale_price' => 0.001388889,
            ]);
        });
    }

    public function testCommand()
    {
        $this->job->handle();
        $this->metric->refresh();

        $this->assertEquals('Networking', $this->metric->category);
        $this->assertEquals(0.001388889, $this->metric->price);
    }
}
