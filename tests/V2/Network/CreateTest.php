<?php

namespace Tests\V2\Network;

use App\Events\V2\Task\Created;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
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

        $this->region = Region::factory()->create();
        $this->availabilityZone = AvailabilityZone::factory()->create([
            'region_id' => $this->region->id,
        ]);
        $this->router = Router::factory()->create([
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone->id
        ]);
    }

    public function testValidDataSucceeds()
    {
        Event::fake([Created::class]);
        $this->asAdmin()
            ->post(
                '/v2/networks',
                [
                    'name' => 'Manchester Network',
                    'router_id' => $this->router->id,
                    'subnet' => '10.0.0.0/24'
                ]
            )->assertStatus(202);
        $this->assertDatabaseHas(
            'networks',
            [
                'name' => 'Manchester Network',
                'router_id' => $this->router->id,
                'subnet' => '10.0.0.0/24'
            ],
            'ecloud'
        );
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
        )->assertJsonFragment(
            [
                'title' => 'Validation Error',
                'detail' => 'The specified router id resource currently has the status of \'failed\' and cannot be used',
            ]
        )->assertStatus(422);
    }
}
