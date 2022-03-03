<?php

namespace Tests\V2\Router;

use App\Events\V2\Task\Created;
use App\Models\V2\RouterThroughput;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateTest extends TestCase
{
    private RouterThroughput $routerThroughput;

    public function setUp(): void
    {
        parent::setUp();

        $this->routerThroughput = RouterThroughput::factory()->create([
            'availability_zone_id' => $this->availabilityZone()->id,
        ]);
    }

    public function testNotOwnedVpcIdIdIsFailed()
    {
        $this->post(
            '/v2/routers',
            [
                'name' => 'Manchester Router 2',
                'vpc_id' => 'x',
            ],
            [
                'X-consumer-custom-id' => '2-0',
                'X-consumer-groups' => 'ecloud.write',
            ])
            ->seeJson([
                'title' => 'Validation Error',
                'detail' => 'The specified vpc id was not found',
                'status' => 422,
                'source' => 'vpc_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testVpcFailureCausesFail()
    {
        // Force failure
        Model::withoutEvents(function () {
            $model = new Task([
                'id' => 'sync-test',
                'failure_reason' => 'Unit Test Failure',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $model->resource()->associate($this->vpc());
            $model->save();
        });

        $data = [
            'name' => 'Manchester Router 1',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'router_throughput_id' => $this->routerThroughput->id
        ];
        $this->post(
            '/v2/routers',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeJson(
            [
                'title' => 'Validation Error',
                'detail' => 'The specified vpc id resource currently has the status of \'failed\' and cannot be used',
            ]
        )->assertResponseStatus(422);
    }

    public function testValidDataSucceeds()
    {
        Event::fake(Created::class);
        $data = [
            'name' => 'Manchester Router 1',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'router_throughput_id' => $this->routerThroughput->id
        ];
        $this->post(
            '/v2/routers',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
           ->seeInDatabase('routers', $data, 'ecloud')
            ->assertResponseStatus(202);
        Event::assertDispatched(Created::class);
    }
}
