<?php

namespace Tests\Unit\Listeners\V2\Instance;

use App\Events\V2\Task\Updated;
use App\Listeners\V2\Instance\UpdateResourceTierBilling;
use App\Models\V2\BillingMetric;
use App\Models\V2\HostGroup;
use App\Models\V2\HostSpec;
use App\Models\V2\Product;
use App\Models\V2\ProductPrice;
use App\Models\V2\ResourceTier;
use App\Models\V2\ResourceTierHostGroup;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class UpdateResourceTierBillingTest extends TestCase
{
    public HostGroup $hostGroup;
    public ResourceTier $resourceTier;

    public function setUp(): void
    {
        parent::setUp();

        Model::withoutEvents(function () {
            ResourceTierHostGroup::factory()
                ->for($this->resourceTier = ResourceTier::factory()
                    ->for($this->availabilityZone())
                    ->create([
                        'id' => 'hs-high-cpu',
                        'name' => 'hs-high-cpu',
                    ]))
                ->for($this->hostGroup = HostGroup::factory()
                    ->for($this->availabilityZone())
                    ->for(HostSpec::factory()
                        ->create([
                            'id' => 'hs-high-cpu',
                        ]))
                    ->create([
                        'id' => 'hg-cf1bae59',
                    ]))
                ->create([
                    'id' => 'rthg-high-cpu',
                ]);
        });

        Product::factory()->create([
            'product_name' => $this->availabilityZone()->id . ': ' . $this->resourceTier->id,
            'product_subcategory' => 'Compute',
        ])->each(function ($product) {
            ProductPrice::factory()->create([
                'product_price_product_id' => $product->id,
                'product_price_sale_price' => 0.05
            ]);
        });

        $this->instanceModel()
            ->hostGroup()
            ->associate($this->hostGroup);
    }

    public function testStandardInstanceNoHighCpuBilling()
    {
        $this->hostGroup()->setAttribute('id', 'hg-9d7e6b43')->saveQuietly();
        $this->assertNull(BillingMetric::getActiveByKey($this->instanceModel(), UpdateResourceTierBilling::getKeyName()));
        $this->instanceModel()->hostGroup()->associate($this->hostGroup());
        $task = $this->createSyncUpdateTask($this->instanceModel());
        $task->setAttribute('completed', true)->saveQuietly();

        (new UpdateResourceTierBilling())->handle(new Updated($task));

        $this->assertDatabaseCount(BillingMetric::class, 0, 'ecloud');
    }

    public function testHighCpuHostGroupAttachedToInstance()
    {
        $this->assertNull(BillingMetric::getActiveByKey($this->instanceModel(), UpdateResourceTierBilling::getKeyName()));
        $this->instanceModel()->hostGroup()->associate($this->hostGroup);
        $task = $this->createSyncUpdateTask($this->instanceModel());
        $task->setAttribute('completed', true)->saveQuietly();

        (new UpdateResourceTierBilling())->handle(new Updated($task));

        $highCpuMetric = BillingMetric::getActiveByKey($this->instanceModel(), UpdateResourceTierBilling::getKeyName());
        $this->assertNotNull($highCpuMetric);
        $this->assertEquals(1, $highCpuMetric->value);
    }

    public function testStandardCpuHostGroupMigrationEndsHighCpuBilling()
    {
        $originalMetric = BillingMetric::factory()
            ->for($this->vpc())
            ->create([
                'name' => UpdateResourceTierBilling::getFriendlyName(),
                'resource_id' => $this->instanceModel()->id,
                'key' => UpdateResourceTierBilling::getKeyName(),
                'value' => 1,
            ]);

        $this->instanceModel()->hostGroup()->associate($this->hostGroup());

        $task = $this->createSyncUpdateTask($this->instanceModel());
        $task->setAttribute('completed', true)->saveQuietly();

        (new UpdateResourceTierBilling())->handle(new Updated($task));

        $originalMetric->refresh();
        $this->assertNotNull($originalMetric->end);
    }
}
