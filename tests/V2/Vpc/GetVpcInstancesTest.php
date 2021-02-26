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

    public function testInstancesCollection()
    {
        $instance = $this->instance();

        $this->get(
            '/v2/vpcs/'.$this->vpc()->id.'/instances',
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups'    => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'       => $instance->id,
                'name'     => $instance->name,
                'vpc_id'   => $instance->vpc_id,
                'platform' => $instance->platform,
            ])
            ->assertResponseStatus(200);
    }
}
