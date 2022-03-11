<?php

namespace Tests\V2\Vpc;

use App\Models\V2\Appliance;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Instance;
use App\Models\V2\Region;
use App\Models\V2\Volume;
use App\Models\V2\Vpc;
use Illuminate\Foundation\Testing\DatabaseMigrations;;
use Tests\TestCase;

class GetVpcInstancesTest extends TestCase
{
    public function testInstancesCollection()
    {
        $instance = $this->instanceModel();

        $this->get(
            '/v2/vpcs/'.$this->vpc()->id.'/instances',
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups'    => 'ecloud.read',
            ]
        )
            ->assertJsonFragment([
                'id'       => $instance->id,
                'name'     => $instance->name,
                'vpc_id'   => $instance->vpc_id,
                'platform' => $instance->platform,
            ])
            ->assertStatus(200);
    }
}
