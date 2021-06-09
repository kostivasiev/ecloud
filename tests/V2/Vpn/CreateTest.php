<?php

namespace Tests\V2\Vpn;

use App\Models\V2\Task;
use App\Models\V2\Vpn;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreateTest extends TestCase
{
    public function testNotUserOwnedRouterIdIsFailed()
    {
        $this->post(
            '/v2/vpns',
            [
                'name' => 'Unit Test VPN',
                'router_id' => $this->router()->id,
            ],
            [
                'X-consumer-custom-id' => '2-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title' => 'Validation Error',
                'detail' => 'The specified router id was not found',
                'status' => 422,
                'source' => 'router_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testRouterFailCausesFail()
    {
        // Force failure
        Model::withoutEvents(function () {
            $model = new Task([
                'id' => 'sync-test',
                'failure_reason' => 'Unit Test Failure',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $model->resource()->associate($this->router());
            $model->save();
        });

        $this->post(
            '/v2/vpns',
            [
                'name' => 'Unit Test VPN',
                'router_id' => $this->router()->id,
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

    public function testValidDataSucceeds()
    {
        $this->post(
            '/v2/vpns',
            [
                'name' => 'Unit Test VPN',
                'router_id' => $this->router()->id,
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertResponseStatus(202);
        $vpnId = (json_decode($this->response->getContent()))->data->id;
        $vpnItem = Vpn::findOrFail($vpnId);
        $this->assertEquals($vpnItem->router_id, $this->router()->id);
    }
}
