<?php

namespace Tests\Unit\Middleware\OrchestratorConfig;

use App\Http\Middleware\OrchestratorConfig\IsValid;
use App\Models\V2\OrchestratorConfig;
use Illuminate\Http\Request;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class IsValidTest extends TestCase
{
    protected OrchestratorConfig $orchestratorConfig;

    public function setUp(): void
    {
        parent::setUp();
        $this->orchestratorConfig = OrchestratorConfig::factory()->create();
    }

    public function testIsNotValidOrchestratorConfigFails()
    {
        $this->be((new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        $request = Request::create(
            'POST',
            '/v2/orchestrator-configs/' . $this->orchestratorConfig->id . '/data',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            'INVALID JSON');

        $middleware = new IsValid();

        $response = $middleware->handle($request, function () {});

        $this->assertEquals($response->getStatusCode(), 422);
    }

    public function testIsValidOrchestratorConfigPasses()
    {
        $this->be((new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        $request = Request::create(
            'POST',
            '/v2/orchestrator-configs/' . $this->orchestratorConfig->id . '/data',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode(['foo' => 'bar']));

        $middleware = new IsValid();

        $response = $middleware->handle($request, function () {});

        $this->assertNull($response);
    }
}
