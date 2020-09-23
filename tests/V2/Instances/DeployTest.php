<?php

namespace Tests\V2\Instances;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Instance;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class DeployTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * @var Vpc
     */
    protected $vpc;

    /**
     * @var Instance
     */
    protected $instance;

    public function setUp(): void
    {
        parent::setUp();
        $this->vpc = factory(Vpc::class)->create();
        $region = factory(Region::class)->create();
        $availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $region->getKey(),
        ]);
        $this->instance = factory(Instance::class)->create([
            'vpc_id' => $this->vpc->getKey(),
            'availability_zone_id' => $availabilityZone->getKey(),
        ]);
    }

    public function testDeploy()
    {
        $this->expectsJobs(\App\Jobs\Instance\Deploy\Deploy::class);
        $this->post('/v2/instances/' . $this->instance->getKey() . '/deploy', [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(202);
    }
}
