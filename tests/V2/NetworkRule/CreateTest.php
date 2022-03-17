<?php
namespace Tests\V2\NetworkRule;

use App\Events\V2\Task\Created;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CreateTest extends TestCase
{
    public function testCreateResource()
    {
        Event::fake([Created::class]);
        $this->vpc()->advanced_networking = true;
        $this->vpc()->saveQuietly();

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        $data = [
            'network_policy_id' => $this->networkPolicy()->id,
            'sequence' => 1,
            'source' => '10.0.1.0/32',
            'destination' => '10.0.2.0/32',
            'action' => 'ALLOW',
            'enabled' => true,
            'direction' => 'IN_OUT'
        ];

        $this->post(
            '/v2/network-rules',
            $data
        )->assertJsonStructure([
           'data' => [
               'id',
               'task_id'
           ]
        ])->assertStatus(202);
        $this->assertDatabaseHas(
            'network_rules',
            $data,
            'ecloud'
        );

        Event::assertDispatched(\App\Events\V2\Task\Created::class);
    }

    public function testCreateResourceNetworkPolicyFailed()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        // Force failure
        Model::withoutEvents(function () {
            $model = new Task([
                'id' => 'sync-test',
                'failure_reason' => 'Unit Test Failure',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $model->resource()->associate($this->networkPolicy());
            $model->save();
        });

        $data = [
            'network_policy_id' => $this->networkPolicy()->id,
            'sequence' => 1,
            'source' => '10.0.1.0/32',
            'destination' => '10.0.2.0/32',
            'action' => 'ALLOW',
            'enabled' => true,
            'direction' => 'IN_OUT'
        ];

        $this->post(
            '/v2/network-rules',
            $data
        )->assertJsonFragment(
            [
                'title' => 'Validation Error',
                'detail' => 'The specified network policy id resource currently has the status of \'failed\' and cannot be used',
            ]
        )->assertStatus(422);
    }
}
