<?php

namespace Tests\Unit\Listeners\V2\ResourceTier;

use App\Events\V2\Task\Created;
use App\Events\V2\Task\Updated;
use App\Listeners\V2\ResourceTier\UpdateBilling;
use App\Models\V2\BillingMetric;
use App\Models\V2\HostGroup;
use App\Models\V2\HostSpec;
use App\Models\V2\Product;
use App\Models\V2\ProductPrice;
use App\Models\V2\ResourceTier;
use App\Models\V2\ResourceTierHostGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UpdateBillingTest extends TestCase
{
    public ResourceTier $resourceTier;
    public HostGroup $hostGroup;

    public function setUp(): void
    {
        parent::setUp();

        Product::factory()->create([
            'product_name' => $this->availabilityZone()->id . ': high cpu',
        ])->each(function ($product) {
            ProductPrice::factory()->create([
                'product_price_product_id' => $product->id,
                'product_price_sale_price' => 1,
            ]);
        });
        Model::withoutEvents(function () {
            ResourceTierHostGroup::factory()
                ->for(
                    $this->hostGroup = HostGroup::factory()
                        ->for(
                            $this->vpc()
                        )->for(
                            $this->availabilityZone()
                        )->for(
                            HostSpec::factory()->create([
                                'id' => 'hs-high-cpu',
                            ])
                        )->create([
                            'id' => 'hg-high-cpu',
                        ])
                )->for(
                    $this->resourceTier = ResourceTier::factory()
                        ->for(
                            $this->availabilityZone()
                        )->create([
                            'id' => 'rt-high-cpu',
                            'name' => 'rt-high-cpu',
                        ])
                )->create([
                    'id' => 'rthg-high-cpu'
                ]);
        });
    }

    public function testStartsBillingMetricForHighCpuEnabled()
    {
        Event::fake(Created::class);

        $task = $this->createSyncUpdateTask($this->resourceTier);
        $task->setAttribute('completed', true)->saveQuietly();

        (new UpdateBilling())->handle(new Updated($task));

        $metric = BillingMetric::getActiveByKey($this->resourceTier, UpdateBilling::getKeyName());
        $this->assertNotNull($metric);
        $this->assertEquals(1, $metric->value);
    }

    public function testEndsBillingMetricForHighCpu()
    {
        $originalMetric = BillingMetric::factory()->create([
            'id' => 'bm-test',
            'resource_id' => $this->resourceTier->id,
            'vpc_id' => $this->vpc()->id,
            'key' => UpdateBilling::getKeyName(),
            'value' => 1,
            'start' => '2020-07-07T10:30:00+01:00',
        ]);

        $this->assertNotNull(BillingMetric::getActiveByKey($this->resourceTier, UpdateBilling::getKeyName()));

        $this->resourceTier->delete();

        $originalMetric->refresh();
        $this->assertNotNull($originalMetric->end);

        $this->assertNull(BillingMetric::getActiveByKey($this->vpc(), UpdateBilling::getKeyName()));
    }
}
