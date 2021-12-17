<?php

namespace Tests\unit\Jobs\Nsx\FirewallPolicy;

use App\Jobs\Nsx\FirewallPolicy\DeployCheck;
use App\Models\V2\FirewallPolicy;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeployCheckTest extends TestCase
{
    protected FirewallPolicy $firewallPolicy;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testNoRulesSucceeds()
    {
        $this->firewallPolicy = Model::withoutEvents(function () {
            return factory(FirewallPolicy::class)->create([
                'id' => 'fwp-test',
                'router_id' => $this->router()->id,
            ]);
        });

        Event::fake([JobFailed::class]);

        dispatch(new DeployCheck($this->firewallPolicy));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testFirewallPolicyRealizedNotReleasedAndSucceeds()
    {
        Model::withoutEvents(function () {
            $this->firewallPolicy = factory(FirewallPolicy::class)->create([
                'id' => 'fwp-test',
                'router_id' => $this->router()->id,
            ]);

            $this->firewallPolicy->firewallRules()->create([
                'id' => 'fwr-test-1',
                'name' => 'fwr-test-1',
                'sequence' => 2,
                'source' => '192.168.1.1',
                'destination' => '192.168.1.2',
                'action' => 'REJECT',
                'direction' => 'IN',
                'enabled' => true,
            ]);
        });

        $this->nsxServiceMock()->expects('get')
            ->withArgs([
                'policy/api/v1/infra/realized-state/status?intent_path=/infra/domains/default/gateway-policies/fwp-test'
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'publish_status' => 'REALIZED'
                ]));
            });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new DeployCheck($this->firewallPolicy));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testFirewallPolicyNotRealizedReleased()
    {
        Model::withoutEvents(function () {
            $this->firewallPolicy = factory(FirewallPolicy::class)->create([
                'id' => 'fwp-test',
                'router_id' => $this->router()->id,
            ]);

            $this->firewallPolicy->firewallRules()->create([
                'id' => 'fwr-test-1',
                'name' => 'fwr-test-1',
                'sequence' => 2,
                'source' => '192.168.1.1',
                'destination' => '192.168.1.2',
                'action' => 'REJECT',
                'direction' => 'IN',
                'enabled' => true,
            ]);
        });

        $this->nsxServiceMock()->expects('get')
            ->withArgs([
                'policy/api/v1/infra/realized-state/status?intent_path=/infra/domains/default/gateway-policies/fwp-test'
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'publish_status' => 'invalid'
                ]));
            });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new DeployCheck($this->firewallPolicy));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }
}
