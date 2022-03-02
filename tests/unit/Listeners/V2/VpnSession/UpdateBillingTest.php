<?php
namespace Tests\unit\Listeners\V2\VpnSession;

use App\Models\V2\BillingMetric;
use App\Models\V2\Product;
use App\Models\V2\ProductPrice;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Tests\Mocks\Resources\VpnSessionMock;
use Tests\TestCase;

class UpdateBillingTest extends TestCase
{
    protected Task $task;

    use VpnSessionMock;

    public function setUp(): void
    {
        parent::setUp();
        factory(Product::class)->create([
            'product_name' => $this->availabilityZone()->id . ': site to site vpn',
            'product_subcategory' => 'Networking',
        ])->each(function ($product) {
            factory(ProductPrice::class)->create([
                'product_price_product_id' => $product->id,
                'product_price_sale_price' => 0.05
            ]);
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

    public function testVpnSessionAddsBillingMetric()
    {
        Model::withoutEvents(function() {
            $this->task = new Task([
                'id' => 'task-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->vpnSession());
        });

        // Check that the vpn.site-to-site.session billing metric is added
        $dispatchResourceSyncedEventListener = new \App\Listeners\V2\VpnSession\UpdateBilling();

        $dispatchResourceSyncedEventListener->handle(new \App\Events\V2\Task\Updated($this->task));

        $metric = BillingMetric::where('resource_id', $this->vpnSession()->id)->first();

        $this->assertNotNull($metric);
        $this->assertEquals('vpn.session.ipsec', $metric->key);
        $this->assertEquals(0.05, $metric->price);
        $this->assertEquals('Networking', $metric->category);
    }

    public function testDeleteVpnSessionEndsBillingMetric()
    {
        $this->hostGroup();

        $billingMetric = BillingMetric::factory()->create([
            'resource_id' => $this->vpnSession()->id,
            'key' => 'vpn.session.ipsec',
            'value' => 1,
        ]);

        $this->assertNull($billingMetric->end);

        $UpdateBillingListener = new \App\Listeners\V2\BillingMetric\End;
        $UpdateBillingListener->handle(new \App\Events\V2\VpnSession\Deleted($this->vpnSession()));

        $this->assertNotNull($billingMetric->refresh()->end);
    }
}
