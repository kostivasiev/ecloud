<?php
namespace Tests\V2\OrchestratorConfig;

use App\Models\V2\OrchestratorConfig;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class UpdateTest extends TestCase
{
    protected OrchestratorConfig $orchestratorConfig;

    public function setUp(): void
    {
        parent::setUp();
        $this->orchestratorConfig = factory(OrchestratorConfig::class)->create();
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
    }

    public function testUpdateAdminSucceeds()
    {
        $this->patch(
            '/v2/orchestrator-configs/' . $this->orchestratorConfig->id,
            [
                'reseller_id' => 2,
                'employee_id' => 2,
            ]
        )->seeInDatabase(
            'orchestrator_configs',
            [
                'reseller_id' => 2,
                'employee_id' => 2,
            ],
            'ecloud'
        )->assertResponseStatus(200);
    }

    public function testUpdateNotAdminFails()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->patch('/v2/orchestrator-configs/' . $this->image()->id, [])->assertResponseStatus(401);
    }
}
