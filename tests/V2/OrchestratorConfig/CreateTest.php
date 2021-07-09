<?php

namespace Tests\V2\OrchestratorConfig;

use App\Models\V2\OrchestratorConfig;
use App\Support\Sync;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CreateTest extends TestCase
{
    protected OrchestratorConfig $orchestratorConfig;

    public function setUp(): void
    {
        parent::setUp();
        $this->orchestratorConfig = factory(OrchestratorConfig::class)->create();
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
    }

    public function testStoreAdminIsSuccess()
    {
        $data = [
            'reseller_id' => 1,
            'employee_id' => 1,
        ];

        $this->post('/v2/orchestrator-configs', $data)
            ->seeInDatabase('orchestrator_configs', $data, 'ecloud')
            ->assertResponseStatus(201);
    }

    public function testStoreDeployDateInPastFails()
    {
        $data = [
            'reseller_id' => 1,
            'employee_id' => 1,
            'deploy_on' => Carbon::yesterday()->format('Y-m-d H:i:s')
        ];
        $this->post('/v2/orchestrator-configs', $data)
            ->seeJson(
                [
                    'title' => 'Validation Error',
                    'detail' => 'The deploy on must be a date after now',
                ]
            )->assertResponseStatus(422);
    }

    public function testStoreDeployDateInFuturePasses()
    {
        $data = [
            'reseller_id' => 1,
            'employee_id' => 1,
            'deploy_on' => Carbon::tomorrow()->format('Y-m-d H:i:s')
        ];
        $this->post('/v2/orchestrator-configs', $data)
            ->seeInDatabase('orchestrator_configs', $data, 'ecloud')
            ->assertResponseStatus(201);
    }

    public function testStoreNotAdminFails()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->post('/v2/orchestrator-configs', [])->assertResponseStatus(401);
    }

    public function testStoreDataAdminIsSuccess()
    {
        $data = [
            'foo' => 'bar'
        ];

        $this->json('POST', '/v2/orchestrator-configs/' . $this->orchestratorConfig->id . '/data', $data)
            ->assertResponseStatus(200);

        $this->orchestratorConfig->refresh();

        $this->assertEquals(json_encode($data), $this->orchestratorConfig->data);
    }

    public function testStoreDataInvalidJsonFails()
    {
        $this->json('POST', '/v2/orchestrator-configs/' . $this->orchestratorConfig->id . '/data', [])
            ->assertResponseStatus(422);
    }

    public function testDeploy()
    {
        Event::fake([\App\Events\V2\Task\Created::class]);

        $this->json('POST', '/v2/orchestrator-configs/' . $this->orchestratorConfig->id . '/deploy', [])
            ->assertResponseStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_UPDATE;
        });
    }
}
