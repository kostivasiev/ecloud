<?php
namespace Tests\V2\OrchestratorConfig;

use App\Models\V2\OrchestratorConfig;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class DeleteTest extends TestCase
{
    protected OrchestratorConfig $orchestratorConfig;

    public function setUp(): void
    {
        parent::setUp();
        $this->orchestratorConfig = OrchestratorConfig::factory()->create();
    }

    public function testAdminDeleteSucceeds()
    {
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        $this->delete('/v2/orchestrator-configs/' . $this->orchestratorConfig->id)->assertStatus(204);
    }

    public function testNotAdminDeleteFails()
    {
        $this->delete('/v2/orchestrator-configs/' . $this->orchestratorConfig->id)->assertStatus(401);
    }
}