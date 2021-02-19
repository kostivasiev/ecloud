<?php

namespace Tests\V2\Vpc;

use App\Models\V2\Appliance;
use App\Models\V2\ApplianceVersion;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Instance;
use App\Models\V2\Region;
use App\Models\V2\Volume;
use App\Models\V2\Vpc;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetVpcInstancesTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
        $instance = $this->instance();
    }

    public function testInstancesCollection()
    {
        $this->get(
            '/v2/vpcs/'.$this->vpc()->getKey().'/instances',
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups'    => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'       => $this->instances[0]->id,
                'name'     => $this->instances[0]->name,
                'vpc_id'   => $this->instances[0]->vpc_id,
                'platform' => $this->instances[0]->platform,
            ])
            ->assertResponseStatus(200);
    }
}
