<?php

namespace Tests\V2\OrchestratorConfig;

use App\Models\V2\OrchestratorConfig;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class LockTest extends TestCase
{
    protected OrchestratorConfig $orchestratorConfig;
    protected Consumer $adminUser;
    protected Consumer $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->orchestratorConfig = OrchestratorConfig::factory()->create();
        $this->user = (new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->adminUser = (new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']))
            ->setIsAdmin(true);
    }

    public function testLockConfigurationNonAdmin()
    {
        $this->be($this->user);
        $this->put('/v2/orchestrator-configs/' . $this->orchestratorConfig->id . '/lock')
            ->seeJson(
                [
                    'title' => 'Unauthorized',
                    'detail' => 'Unauthorized',
                ]
            )->assertResponseStatus(401);
    }

    public function testLockConfigurationAsAdmin()
    {
        $this->be($this->adminUser);
        $this->put('/v2/orchestrator-configs/' . $this->orchestratorConfig->id . '/lock')
            ->assertResponseStatus(204);
        $this->orchestratorConfig->refresh();
        $this->assertTrue($this->orchestratorConfig->locked);
    }

    public function testUnlockConfigurationNonAdmin()
    {
        $this->be($this->user);
        $this->put('/v2/orchestrator-configs/' . $this->orchestratorConfig->id . '/unlock')
            ->seeJson(
                [
                    'title' => 'Unauthorized',
                    'detail' => 'Unauthorized',
                ]
            )->assertResponseStatus(401);
    }

    public function testUnlockConfigurationAsAdmin()
    {
        $this->be($this->adminUser);
        // lock the config
        $this->orchestratorConfig->locked = true;
        $this->orchestratorConfig->saveQuietly();

        $this->put('/v2/orchestrator-configs/' . $this->orchestratorConfig->id . '/unlock')
            ->assertResponseStatus(204);
        $this->orchestratorConfig->refresh();
        $this->assertFalse($this->orchestratorConfig->locked);
    }

    public function testUpdateDataOnLockedConfig()
    {
        $this->be($this->adminUser);
        // lock the config
        $this->orchestratorConfig->locked = true;
        $this->orchestratorConfig->saveQuietly();

        $this->json(
            'POST',
            '/v2/orchestrator-configs/' . $this->orchestratorConfig->id . '/data',
            [
                'foo' => 'bar'
            ]
        )->seeJson(
            [
                'title' => 'Forbidden',
                'detail' => 'The specified Orchestrator Config is locked',
            ]
        )->assertResponseStatus(403);
    }
}
