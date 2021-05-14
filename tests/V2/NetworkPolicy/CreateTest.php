<?php
namespace Tests\V2\NetworkPolicy;

use App\Models\V2\NetworkPolicy;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CreateTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        app()->bind(NetworkPolicy::class, function () {
            $networkPolicy = \Mockery::mock($this->networkPolicy())->makePartial();
            $networkPolicy->expects('syncSave')
                ->andReturnUsing(function () use ($networkPolicy) {
                    $networkPolicy->save();
                    $task = app()->make(Task::class);
                    $task->id = 'test-task';
                    $task->name = $task->id;
                    $task->resource()->associate($networkPolicy);
                    $task->completed = false;
                    $task->save();
                    return $task;
                });

            return $networkPolicy;
        });

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testCreateResource()
    {
        $data = [
            'name' => 'Test Policy',
            'network_id' => $this->network()->id,
        ];
        $this->post(
            '/v2/network-policies',
            $data
        )->seeJson([
            'id' => 'np-test',
            'task_id' => 'test-task',
        ])->seeInDatabase(
            'network_policies',
            [
                'name' => 'Test Policy',
                'network_id' => $this->network()->id,
            ],
            'ecloud'
        )->assertResponseStatus(202);
    }

    public function testCreateResourceFailedNetwork()
    {
        // Force failure
        Model::withoutEvents(function () {
            $model = new Task([
                'id' => 'sync-test',
                'failure_reason' => 'Unit Test Failure',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $model->resource()->associate($this->network());
            $model->save();
        });

        $data = [
            'name' => 'Test Policy',
            'network_id' => $this->network()->id,
        ];
        $this->post(
            '/v2/network-policies',
            $data
        )->seeJson(
            [
                'title' => 'Validation Error',
                'detail' => 'The specified network id resource is currently in a failed state and cannot be used',
            ]
        )->assertResponseStatus(422);
    }

    public function testCreateResourceNetworkAlreadyAssigned()
    {
        $data = [
            'name' => 'Test Policy',
            'network_id' => $this->network()->id,
        ];
        factory(NetworkPolicy::class)->create(array_merge(['id' => 'np-test'], $data));
        $this->post(
            '/v2/network-policies',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeJson([
            'title' => 'Validation Error',
            'detail' => 'This network id already has an assigned Policy'
        ])->assertResponseStatus(422);
    }
}