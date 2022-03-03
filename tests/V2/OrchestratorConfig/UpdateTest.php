<?php
namespace Tests\V2\OrchestratorConfig;

use App\Models\V2\OrchestratorConfig;
use Carbon\Carbon;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class UpdateTest extends TestCase
{
    protected OrchestratorConfig $orchestratorConfig;

    public function setUp(): void
    {
        parent::setUp();
        $this->orchestratorConfig = OrchestratorConfig::factory()->create();
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
        )->assertStatus(200);
        $this->assertDatabaseHas(
            'orchestrator_configs',
            [
                'reseller_id' => 2,
                'employee_id' => 2,
            ],
            'ecloud'
        );
    }

    public function testStoreDeployDateInPastFails()
    {
        $data = [
            'reseller_id' => 1,
            'employee_id' => 1,
            'deploy_on' => Carbon::yesterday()->format('Y-m-d H:i:s')
        ];
        $this->patch('/v2/orchestrator-configs/' . $this->orchestratorConfig->id, $data)
            ->assertJsonFragment(
                [
                    'title' => 'Validation Error',
                    'detail' => 'The deploy on must be a date after now',
                ]
            )->assertStatus(422);
    }

    public function testStoreDeployDateInFuturePasses()
    {
        $data = [
            'reseller_id' => 1,
            'employee_id' => 1,
            'deploy_on' => Carbon::tomorrow()->format('Y-m-d H:i:s')
        ];
        $this->patch('/v2/orchestrator-configs/' . $this->orchestratorConfig->id, $data)
            ->assertStatus(200);
        $this->assertDatabaseHas('orchestrator_configs', $data, 'ecloud');
    }

    public function testStoreDeployDateNoResellerIdFails()
    {
        $this->orchestratorConfig->reseller_id = null;
        $this->orchestratorConfig->saveQuietly();

        $data = [
            'employee_id' => 1,
            'deploy_on' => Carbon::tomorrow()->format('Y-m-d H:i:s')
        ];
        $this->patch('/v2/orchestrator-configs/' . $this->orchestratorConfig->id, $data)
            ->assertJsonFragment(
                [
                    'title' => 'Validation Error',
                    'detail' => 'The reseller id is required when specifying deploy on property',
                ]
            )->assertStatus(422);
    }

    public function testUpdateNotAdminFails()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->patch('/v2/orchestrator-configs/' . $this->image()->id, [])->assertStatus(401);
    }
}
