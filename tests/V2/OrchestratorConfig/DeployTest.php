<?php

namespace Tests\V2\OrchestratorConfig;

use App\Models\V2\OrchestratorConfig;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class DeployTest extends TestCase
{
    protected OrchestratorConfig $orchestratorConfig;

    public function setUp(): void
    {
        parent::setUp();
        $this->orchestratorConfig = OrchestratorConfig::factory()->create();
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
    }

    public function testDeployAdminSucceeds()
    {
        Event::fake([\App\Events\V2\Task\Created::class]);

        $this->post('/v2/orchestrator-configs/' . $this->orchestratorConfig->id. '/deploy')->assertStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == 'sync_update';
        });
    }

    public function testDeployNotAdminFails()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->post('/v2/orchestrator-configs/' . $this->orchestratorConfig->id. '/deploy')->assertStatus(401);
    }
}
