<?php

namespace Tests\unit\Jobs\OrchestratorBuild;

use App\Events\V2\Task\Created;
use App\Jobs\OrchestratorBuild\CreateHosts;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\OrchestratorConfig;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CreateHostsTest extends TestCase
{
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
        $this->orchestratorConfig = OrchestratorConfig::factory()->create([
            'reseller_id' => 7052,
            'employee_id' => 1,
            'data' => json_encode($this->orchestratorData),
            'locked' => false,
        ]);
        $this->orchestratorBuild = OrchestratorBuild::factory()->create([
            'orchestrator_config_id' => $this->orchestratorConfig->id,
            'state' => [],
        ]);
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
                CreateHosts::class . ' : OrchestratorBuild does not contain any Hosts, skipping'
            )->andThrow(new \Exception('No Hosts'));

        (new CreateHosts($this->orchestratorBuild))->handle();
    }

    /** @test */
    public function hostHasBeenProcessed()
    {
        $this->expectExceptionMessage('Host Initiated');
        Log::partialMock()
            ->expects('info')
            ->once()
            ->withSomeOfArgs(
                CreateHosts::class . ' : OrchestratorBuild host. 0 has already been initiated, skipping'
            )->andThrow(new \Exception('Host Initiated'));

        $this->orchestratorBuild->state = ['host' => ['0']];
        $this->orchestratorBuild->save();

        (new CreateHosts($this->orchestratorBuild))->handle();
    }

    /** @test */
    public function hostIsCreated()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);


        dispatch(new CreateHosts($this->orchestratorBuild));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        Event::assertDispatched(Created::class);

        $this->orchestratorBuild->refresh();
        $this->assertNotNull($this->orchestratorBuild->state['host']);
        $this->assertEquals(1, count($this->orchestratorBuild->state['host']));
    }
}
