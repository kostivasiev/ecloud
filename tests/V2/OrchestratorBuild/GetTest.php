<?php

namespace Tests\V2\OrchestratorBuild;

use App\Models\V2\OrchestratorBuild;
use App\Models\V2\OrchestratorConfig;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class GetTest extends TestCase
{
    protected OrchestratorConfig $orchestratorConfig;

    protected OrchestratorBuild $orchestratorBuild;

    public function setUp(): void
    {
        parent::setUp();

        $this->orchestratorConfig = factory(OrchestratorConfig::class)->create();

        $this->orchestratorBuild = factory(OrchestratorBuild::class)->make();
        $this->orchestratorBuild->orchestratorConfig()->associate($this->orchestratorConfig);
        $this->orchestratorBuild->save();

        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
    }

    public function testIndexAdminSucceeds()
    {
        $this->get('/v2/orchestrator-builds')
            ->seeJson([
                'id' => $this->orchestratorBuild->id,
                'orchestrator_config_id' => $this->orchestratorConfig->id,
                'state' => null
            ])
            ->assertResponseStatus(200);
    }

    public function testIndexNotAdminFails()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->get('/v2/orchestrator-builds')->assertResponseStatus(401);
    }

    public function testShowAdminSucceeds()
    {
        $this->get('/v2/orchestrator-builds/' . $this->orchestratorBuild->id)
            ->seeJson([
                'id' => $this->orchestratorBuild->id,
                'orchestrator_config_id' => $this->orchestratorConfig->id,
                'state' => null
            ])
            ->assertResponseStatus(200);
    }

    public function testShowNotAdminFails()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->get('/v2/orchestrator-builds/' . $this->orchestratorConfig->id)->assertResponseStatus(401);
    }
}
