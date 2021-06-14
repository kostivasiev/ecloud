<?php

namespace Tests\V2\Vpn;

use App\Models\V2\Task;
use App\Models\V2\Vpn;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    protected $vpn;

    public function setUp(): void
    {
        parent::setUp();

//        $this->vpn = factory(Vpn::class)->create([
//            'router_id' => $this->router()->id,
//        ]);
    }

    public function testNotOwnedRouterResourceIsFailed()
    {
        $this->markTestSkipped('Skipped due to VPN refactor');
//        $this->vpc()->reseller_id = 3;
//        $this->vpc()->saveQuietly();
//
//        $this->patch(
//            '/v2/vpns/' . $this->vpn->id,
//            [
//                'router_id' => $this->router()->id,
//            ],
//            [
//                'X-consumer-custom-id' => '1-0',
//                'X-consumer-groups' => 'ecloud.write',
//            ]
//        )
//            ->seeJson([
//                'title' => 'Validation Error',
//                'detail' => 'The specified router id was not found',
//                'status' => 422,
//                'source' => 'router_id'
//            ])
//            ->assertResponseStatus(422);
    }

    public function testFailedRouterCausesFailure()
    {
        $this->markTestSkipped('Skipped due to VPN refactor');
//        // Force failure
//        Model::withoutEvents(function () {
//            $model = new Task([
//                'id' => 'sync-test',
//                'failure_reason' => 'Unit Test Failure',
//                'completed' => true,
//                'name' => Sync::TASK_NAME_UPDATE,
//            ]);
//            $model->resource()->associate($this->router());
//            $model->save();
//        });
//
//        $data = [
//            'router_id' => $this->router()->id,
//        ];
//        $this->patch(
//            '/v2/vpns/' . $this->vpn->id,
//            $data,
//            [
//                'X-consumer-custom-id' => '0-0',
//                'X-consumer-groups' => 'ecloud.write',
//            ]
//        )->seeJson(
//            [
//                'title' => 'Validation Error',
//                'detail' => 'The specified router id resource is currently in a failed state and cannot be used',
//            ]
//        )->assertResponseStatus(422);
    }

    public function testValidDataIsSuccessful()
    {
        $this->markTestSkipped('Skipped due to VPN refactor');
//        $data = [
//            'router_id' => $this->router()->id,
//        ];
//        $this->patch(
//            '/v2/vpns/' . $this->vpn->id,
//            $data,
//            [
//                'X-consumer-custom-id' => '0-0',
//                'X-consumer-groups' => 'ecloud.write',
//            ]
//        )->assertResponseStatus(200);
//
//        $vpnItem = Vpn::findOrFail($this->vpn->id);
//        $this->assertEquals($data['router_id'], $vpnItem->router_id);
    }
}
