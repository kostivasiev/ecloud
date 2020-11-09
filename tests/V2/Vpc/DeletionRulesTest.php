<?php

namespace Tests\V2\Vpc;

use App\Models\V2\Appliance;
use App\Models\V2\ApplianceVersion;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Dhcp;
use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Models\V2\Nic;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use App\Services\V2\KingpinService;
use Faker\Factory as Faker;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeletionRulesTest extends TestCase
{
    use DatabaseMigrations;

    protected AvailabilityZone $availability_zone;
    protected Network $network;
    protected Region $region;
    protected Router $router;
    protected Dhcp $dhcp;
    protected Vpc $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->region = factory(Region::class)->create();
        $this->availability_zone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
        $this->dhcp = factory(Dhcp::class)->create([
            'vpc_id' => $this->vpc->getKey(),
        ]);
        $this->router = factory(Router::class)->create([
            'vpc_id' => $this->vpc->getKey(),
        ]);
    }

    public function testFailedDeletion()
    {
        $this->delete(
            '/v2/vpcs/' . $this->vpc->getKey(),
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeJson([
            'detail' => 'Active resources exist for this item',
        ])->assertResponseStatus(412);
        $vpc = Vpc::withTrashed()->findOrFail($this->vpc->getKey());
        $this->assertNull($vpc->deleted_at);
    }
}
