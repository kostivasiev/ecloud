<?php

namespace Tests\Unit\Jobs\OrchestratorBuild;

use App\Events\V2\Task\Created;
use App\Jobs\OrchestratorBuild\ConfigureDefaultFirewallPolicies;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\OrchestratorConfig;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ConfigureDefaultPoliciesTest extends TestCase
{
    protected OrchestratorConfig $orchestratorConfig;

    protected OrchestratorBuild $orchestratorBuild;

    public function setUp(): void
    {
        parent::setUp();
        $this->orchestratorConfig = OrchestratorConfig::factory()->create([
            'data' => json_encode([
                'routers' => [
                    [
                        'vpc_id' => '{vpc.0}',
                        'name' => 'test router',
                        'router_throughput_id' => "rtp-test",
                        'availability_zone_id' => "az-test",
                        'configure_default_policies' => true
                    ]
                ]
            ])
        ]);

        $this->routerThroughput();

        $this->orchestratorBuild = OrchestratorBuild::factory()->make();
        $this->orchestratorBuild->orchestratorConfig()->associate($this->orchestratorConfig);
        $this->orchestratorBuild->save();
    }

    public function testNoRouterDataSkips()
    {
        $this->orchestratorConfig->data = null;
        $this->orchestratorConfig->save();

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new ConfigureDefaultFirewallPolicies($this->orchestratorBuild));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testSuccess()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        $this->availabilityZone();
        $this->router();

        $this->orchestratorBuild->updateState('router', 0, 'rtr-test');

        dispatch(new ConfigureDefaultFirewallPolicies($this->orchestratorBuild));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        Event::assertDispatched(Created::class);

        $this->orchestratorBuild->refresh();

        $this->assertNotNull($this->orchestratorBuild->state['default_firewall_policies']);

        $this->assertEquals(
            count(config('firewall.policies')),
            count($this->orchestratorBuild->state['default_firewall_policies']['rtr-test'])
        );
    }
}