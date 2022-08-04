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
            'product_description' => 'High CPU Resource Tier',
        ])->each(function ($product) {
            ProductPrice::factory()->create([
                'product_price_product_id' => $product->id,
                'product_price_sale_price' => 0.05
            ]);
        });

        $this->instanceModel()
            ->hostGroup()
            ->associate($this->hostGroup);
        $this->instanceModel()->setAttribute('vcpu_cores', 1)->saveQuietly();
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
        $this->instanceModel()->setAttribute('vcpu_cores', 2)->saveQuietly();
        $task = $this->createSyncUpdateTask($this->instanceModel());
        $task->setAttribute('completed', true)->saveQuietly();

        (new UpdateResourceTierBilling())->handle(new Updated($task));

        $highCpuMetric = BillingMetric::getActiveByKey(
            $this->instanceModel(),
            UpdateResourceTierBilling::getKeyName() . '.' . $this->resourceTier->id
        );
        $this->assertNotNull($highCpuMetric);
        $this->assertEquals(2, $highCpuMetric->value);
    }

    public function testStandardCpuHostGroupMigrationEndsHighCpuBilling()
    {
        $originalMetric = BillingMetric::factory()
            ->for($this->vpc())
            ->create([
                'name' => UpdateResourceTierBilling::getFriendlyName(),
                'resource_id' => $this->instanceModel()->id,
                'key' => UpdateResourceTierBilling::getKeyName() . '.' . $this->resourceTier->id,
                'value' => 1,
            ]);

        $this->instanceModel()->hostGroup()->associate($this->hostGroup());

        $task = $this->createSyncUpdateTask($this->instanceModel());
        $task->setAttribute('completed', true)->saveQuietly();

        (new UpdateResourceTierBilling())->handle(new Updated($task));

        $originalMetric->refresh();
        $this->assertNotNull($originalMetric->end);
    }

    public function testNewMetricOnCpuUsageChange()
    {
        $this->instanceModel()->hostGroup()->associate($this->hostGroup);
        $originalMetric = BillingMetric::factory()
            ->for($this->vpc())
            ->create([
                'name' => UpdateResourceTierBilling::getFriendlyName(),
                'resource_id' => $this->instanceModel()->id,
                'key' => UpdateResourceTierBilling::getKeyName() . '.' . $this->resourceTier->id,
                'value' => 1,
            ]);
        $this->instanceModel()->setAttribute('vcpu_cores', 2)->saveQuietly();

        $task = $this->createSyncUpdateTask($this->instanceModel());
        $task->setAttribute('completed', true)->saveQuietly();

        (new UpdateResourceTierBilling())->handle(new Updated($task));

        $newMetric = BillingMetric::where('id', '!=', $originalMetric->id)->first();
        $originalMetric->refresh();

        // check original metric is for 1 cpu and that it's ended
        $this->assertEquals(1, $originalMetric->value);
        $this->assertNotNull($originalMetric->end);

        // Check new metric is for 2 cpu and that it's active
        $this->assertEquals(2, $newMetric->value);
        $this->assertEquals(UpdateResourceTierBilling::getKeyName() . '.' . $this->resourceTier->id, $newMetric->key);
        $this->assertNull($newMetric->end);
    }
}
