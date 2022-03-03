<?php

namespace Tests\V2\OrchestratorConfig;

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
        $this->orchestratorConfig = OrchestratorConfig::factory()->create();
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
    }

    public function testIndexAdminSucceeds()
    {
        $this->get('/v2/orchestrator-configs')
            ->assertJsonFragment([
                'reseller_id' => 1,
                'employee_id' => 1,
            ])
            ->assertStatus(200);
    }

    public function testIndexNotAdminFails()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->get('/v2/orchestrator-configs')->assertStatus(401);
    }

    public function testShowAdminSucceeds()
    {
        $this->get('/v2/orchestrator-configs/' . $this->orchestratorConfig->id)
            ->assertJsonFragment([
                'reseller_id' => 1,
                'employee_id' => 1,
            ])
            ->assertStatus(200);
    }

    public function testShowNotAdminFails()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->get('/v2/orchestrator-configs/' . $this->orchestratorConfig->id)->assertStatus(401);
    }

    public function testGetDataAdminSucceeds()
    {
        $this->get('/v2/orchestrator-configs/' . $this->orchestratorConfig->id. '/data')
            ->assertJsonFragment([
                'name' => 'vpc-1',
                'region_id' => 'reg-test',
            ])
            ->assertStatus(200);
    }

    public function testGetDataNotAdminFails()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->get('/v2/orchestrator-configs/' . $this->orchestratorConfig->id. '/data')->assertStatus(401);
    }

    public function testGetBuildsAdminSucceeds()
    {
        $this->orchestratorBuild = OrchestratorBuild::factory()->make();
        $this->orchestratorBuild->orchestratorConfig()->associate($this->orchestratorConfig);
        $this->orchestratorBuild->save();

        $this->get('/v2/orchestrator-configs/' . $this->orchestratorConfig->id. '/builds')
            ->assertJsonFragment([
                'id' => $this->orchestratorBuild->id,
                'orchestrator_config_id' => $this->orchestratorConfig->id,
                'state' => null
            ])
            ->assertStatus(200);
    }

    public function testGetBuildsNotAdminFails()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->get('/v2/orchestrator-configs/' . $this->orchestratorConfig->id. '/builds')->assertStatus(401);
    }
}
