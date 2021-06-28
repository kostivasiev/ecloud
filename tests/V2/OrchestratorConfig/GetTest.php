<?php

namespace Tests\V2\OrchestratorConfig;

use App\Models\V2\OrchestratorBuild;
use App\Models\V2\OrchestratorConfig;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class GetTest extends TestCase
{
    protected OrchestratorConfig $orchestratorConfig;

    public function setUp(): void
    {
        parent::setUp();
        $this->orchestratorConfig = factory(OrchestratorConfig::class)->create();
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
    }

    public function testIndexAdminSucceeds()
    {
        $this->get('/v2/orchestrator-configs')
            ->seeJson([
                'reseller_id' => 1,
                'employee_id' => 1,
            ])
            ->assertResponseStatus(200);
    }

    public function testIndexNotAdminFails()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->get('/v2/orchestrator-configs')->assertResponseStatus(401);
    }

    public function testShowAdminSucceeds()
    {
        $this->get('/v2/orchestrator-configs/' . $this->orchestratorConfig->id)
            ->seeJson([
                'reseller_id' => 1,
                'employee_id' => 1,
            ])
            ->assertResponseStatus(200);
    }

    public function testShowNotAdminFails()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->get('/v2/orchestrator-configs/' . $this->orchestratorConfig->id)->assertResponseStatus(401);
    }

    public function testGetDataAdminSucceeds()
    {
        $this->get('/v2/orchestrator-configs/' . $this->orchestratorConfig->id. '/data')
            ->seeJson([
                'foo' => 'bar'
            ])
            ->assertResponseStatus(200);
    }

    public function testGetDataNotAdminFails()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->get('/v2/orchestrator-configs/' . $this->orchestratorConfig->id. '/data')->assertResponseStatus(401);
    }

    public function testGetBuildsAdminSucceeds()
    {
        $this->orchestratorBuild = factory(OrchestratorBuild::class)->make();
        $this->orchestratorBuild->orchestratorConfig()->associate($this->orchestratorConfig);
        $this->orchestratorBuild->save();

        $this->get('/v2/orchestrator-configs/' . $this->orchestratorConfig->id. '/builds')
            ->seeJson([
                'id' => $this->orchestratorBuild->id,
                'orchestrator_config_id' => $this->orchestratorConfig->id,
                'state' => null
            ])
            ->assertResponseStatus(200);
    }

    public function testGetBuildsNotAdminFails()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->get('/v2/orchestrator-configs/' . $this->orchestratorConfig->id. '/builds')->assertResponseStatus(401);
    }
}
