<?php

namespace Tests\V2\BillingMetric;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\BillingMetric;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
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

        $this->region = factory(Region::class)->create();
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->id
        ]);
        $this->router = Router::factory()->create([
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone->id,
        ]);
        $this->billingMetric = factory(BillingMetric::class)->create([
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

    public function testGetCollection()
    {
        $this->get('/v2/billing-metrics', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read, ecloud.write',
        ])
            ->seeJson([
                'id' => $this->billingMetric->id,
                'resource_id' => $this->billingMetric->resource_id,
                'vpc_id' => $this->billingMetric->vpc_id,
                'reseller_id' => (string)$this->billingMetric->reseller_id,
                'key' => $this->billingMetric->key,
                'value' => $this->billingMetric->value,
                'start' => $this->billingMetric->start,
                'category' => $this->billingMetric->category,
                'price' => $this->billingMetric->price,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->get('/v2/billing-metrics/' . $this->billingMetric->id, [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read, ecloud.write',
        ])
            ->seeJson([
                'id' => $this->billingMetric->id,
                'resource_id' => $this->billingMetric->resource_id,
                'vpc_id' => $this->billingMetric->vpc_id,
                'reseller_id' => (string)$this->billingMetric->reseller_id,
                'key' => $this->billingMetric->key,
                'value' => $this->billingMetric->value,
                'start' => $this->billingMetric->start,
                'category' => $this->billingMetric->category,
                'price' => $this->billingMetric->price,
            ])
            ->assertResponseStatus(200);
    }
}
