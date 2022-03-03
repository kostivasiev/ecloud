<?php

namespace Tests\V2\BillingMetric;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\BillingMetric;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    /** @var Region */
    private $region;

    /** @var AvailabilityZone */
    private $availabilityZone;

    /** @var Vpc */
    private $vpc;

    /** @var Router */
    private $router;

    /** @var BillingMetric */
    private $billingMetric;

    public function setUp(): void
    {
        parent::setUp();

        $this->region = Region::factory()->create();
        $this->availabilityZone = AvailabilityZone::factory()->create([
            'region_id' => $this->region->id
        ]);
        $this->router = Router::factory()->create([
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone->id
        ]);
        $this->billingMetric = BillingMetric::factory()->create([
            'resource_id' => $this->router->id,
            'vpc_id' => $this->vpc()->id,
            'reseller_id' => 1,
            'key' => 'ram.capacity',
            'value' => '16GB',
            'start' => '2020-07-07T10:30:00+01:00',
            'category' => 'test category',
            'price' => 9.99,
        ]);
    }

    public function testValidDataSucceeds()
    {
        $this->patch('/v2/billing-metrics/' . $this->billingMetric->id, [
            'key' => 'changed',
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertStatus(200);
        $resource = BillingMetric::find($this->billingMetric->id);
        $this->assertEquals('changed', $resource->key);
        $this->assertDatabaseHas(
            'billing_metrics',
            [
                'id' => $this->billingMetric->id,
                'key' => 'changed',
            ],
            'ecloud'
        );
    }
}
