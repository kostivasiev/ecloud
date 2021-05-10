<?php

namespace Tests\V2\BillingMetric;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreateTest extends TestCase
{
    /** @var Region */
    private $region;

    /** @var AvailabilityZone */
    private $availabilityZone;

    /** @var Vpc */
    private $vpc;

    /** @var Router */
    private $router;

    public function setUp(): void
    {
        parent::setUp();

        $this->router = factory(Router::class)->create([
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id
        ]);
    }

    public function testValidDataSucceeds()
    {
        $data = [
            'resource_id' => $this->router->id,
            'vpc_id' => $this->vpc()->id,
            'reseller_id' => 1,
            'key' => 'ram.capacity',
            'value' => '16GB',
            'start' => '2020-07-07T10:30:00+01:00',
            'category' => 'test category',
            'price' => 9.99,
        ];
        $this->post('/v2/billing-metrics', $data, [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])
            ->seeInDatabase('billing_metrics', $data, 'ecloud')
            ->assertResponseStatus(201);
    }
}
