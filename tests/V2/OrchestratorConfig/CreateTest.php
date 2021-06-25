<?php

namespace Tests\V2\OrchestratorConfig;

use App\Models\V2\OrchestratorConfig;
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
}
