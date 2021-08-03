<?php

namespace Tests\V2\Network;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Network;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Task;
use App\Models\V2\Vpc;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreateTest extends TestCase
{
    protected $region;
    protected $vpc;
    protected $router;
    protected $availabilityZone;

    public function setUp(): void
    {
        parent::setUp();

        $this->region = factory(Region::class)->create();
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->id,
        ]);
        $this->router = factory(Router::class)->create([
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone->id
        ]);
    }

    public function testRestrictedSubnetFails()
    {
        $this->post(
            '/v2/networks',
            [
                'name' => 'Manchester Network',
                'router_id' => $this->router->id,
                'subnet' => '192.168.0.0/16'
            ],
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The subnet must be a valid private CIDR range',
        ])->assertResponseStatus(422);
    }

    public function testRestrictedSubnetPasses()
    {
        $this->post(
            '/v2/networks',
            [
                'name' => 'Manchester Network',
                'router_id' => $this->router->id,
                'subnet' => '192.168.0.0/16'
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )  ->seeInDatabase(
            'networks',
            [
                'name' => 'Manchester Network',
                'router_id' => $this->router->id,
                'subnet' => '192.168.0.0/16'
            ],
            'ecloud'
        )
            ->assertResponseStatus(202);
    }

    public function testValidDataSucceeds()
    {
        $this->post(
            '/v2/networks',
            [
                'name' => 'Manchester Network',
                'router_id' => $this->router->id,
                'subnet' => '10.0.0.0/24'
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )  ->seeInDatabase(
            'networks',
            [
                'name' => 'Manchester Network',
                'router_id' => $this->router->id,
                'subnet' => '10.0.0.0/24'
            ],
            'ecloud'
        )
            ->assertResponseStatus(202);
    }

    public function testFailedRouterCausesFail()
    {
        // Force failure
        Model::withoutEvents(function () {
            $model = new Task([
                'id' => 'sync-test',
                'failure_reason' => 'Unit Test Failure',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $model->resource()->associate($this->router);
            $model->save();
        });

        $this->post(
            '/v2/networks',
            [
                'name' => 'Manchester Network',
                'router_id' => $this->router->id,
                'subnet' => '10.0.0.0/24'
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeJson(
            [
                'title' => 'Validation Error',
                'detail' => 'The specified router id resource is currently in a failed state and cannot be used',
            ]
        )->assertResponseStatus(422);
    }
}
