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
            ->assertJsonFragment(
                [
                    'title' => 'Unauthorized',
                    'detail' => 'Unauthorized',
                ]
            )->assertStatus(401);
    }

    public function testLockConfigurationAsAdmin()
    {
        $this->be($this->adminUser);
        $this->put('/v2/orchestrator-configs/' . $this->orchestratorConfig->id . '/lock')
            ->assertStatus(204);
        $this->orchestratorConfig->refresh();
        $this->assertTrue($this->orchestratorConfig->locked);
    }

    public function testUnlockConfigurationNonAdmin()
    {
        $this->be($this->user);
        $this->put('/v2/orchestrator-configs/' . $this->orchestratorConfig->id . '/unlock')
            ->assertJsonFragment(
                [
                    'title' => 'Unauthorized',
                    'detail' => 'Unauthorized',
                ]
            )->assertStatus(401);
    }

    public function testUnlockConfigurationAsAdmin()
    {
        $this->be($this->adminUser);
        // lock the config
        $this->orchestratorConfig->locked = true;
        $this->orchestratorConfig->saveQuietly();

        $this->put('/v2/orchestrator-configs/' . $this->orchestratorConfig->id . '/unlock')
            ->assertStatus(204);
        $this->orchestratorConfig->refresh();
        $this->assertFalse($this->orchestratorConfig->locked);
    }

    public function testUpdateDataOnLockedConfig()
    {
        $this->be($this->adminUser);
        // lock the config
        $this->orchestratorConfig->locked = true;
        $this->orchestratorConfig->saveQuietly();

        $this->postJson(
            '/v2/orchestrator-configs/' . $this->orchestratorConfig->id . '/data',
            [
                'foo' => 'bar'
            ]
        )->assertJsonFragment(
            [
                'title' => 'Forbidden',
                'detail' => 'The specified Orchestrator Config is locked',
            ]
        )->assertStatus(403);
    }
}
