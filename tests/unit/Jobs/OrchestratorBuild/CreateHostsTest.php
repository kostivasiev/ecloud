<?php

namespace Tests\unit\Jobs\OrchestratorBuild;

use App\Jobs\OrchestratorBuild\CreateHosts;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\OrchestratorConfig;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Tests\unit\Jobs\OrchestratorBuild\Mocks\CreateHostsMocks;

class CreateHostsTest extends TestCase
{
    use CreateHostsMocks;

    protected CreateHosts $job;
    protected OrchestratorBuild $orchestratorBuild;
    protected OrchestratorConfig $orchestratorConfig;

    protected array $orchestratorData = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->orchestratorData = [
            'hosts' => [
                [
                    'host_group_id' => $this->hostGroup()->id,
                ],
            ]
        ];
        $this->orchestratorConfig = factory(OrchestratorConfig::class)->create(
            [
                'reseller_id' => 7052,
                'employee_id' => 1,
                'data' => json_encode($this->orchestratorData),
                'locked' => false,
            ]
        );
        $this->orchestratorBuild = factory(OrchestratorBuild::class)->create(
            [
                'orchestrator_config_id' => $this->orchestratorConfig->id,
                'state' => [],
            ]
        );
        $this->job = new CreateHosts($this->orchestratorBuild);
        $this->availabilityZone();
    }

    /** @test */
    public function thereAreNoHostsDefined()
    {
        $this->expectExceptionMessage('No Hosts');

        unset($this->orchestratorData['hosts']);
        $this->orchestratorConfig->data = json_encode($this->orchestratorData);
        $this->orchestratorConfig->save();

        Log::partialMock()
            ->expects('info')
            ->once()
            ->withSomeOfArgs(
                get_class($this->job) . ' : OrchestratorBuild does not contain any Hosts, skipping'
            )->andThrow(new \Exception('No Hosts'));

        $this->job->handle();
    }

    /** @test */
    public function hostHasBeenProcessed()
    {
        $this->expectExceptionMessage('Host Initiated');
        Log::partialMock()
            ->expects('info')
            ->once()
            ->withSomeOfArgs(
                get_class($this->job) . ' : OrchestratorBuild host. 0 has already been initiated, skipping'
            )->andThrow(new \Exception('Host Initiated'));

        $this->orchestratorBuild->state = ['host' => ['0']];
        $this->orchestratorBuild->save();

        $this->job->handle();
    }

    /** @test */
    public function hostIsCreated()
    {
        $this->buildCreateHostIsCreatedMocks();

        $this->job->handle();

        $this->assertArrayHasKey('host', $this->orchestratorBuild->state);
        $this->assertEquals($this->orchestratorBuild->state['host'][0], $this->host()->id);
    }
}
