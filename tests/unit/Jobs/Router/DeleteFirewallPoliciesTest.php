<?php

namespace Tests\unit\Jobs\Router;

use App\Jobs\Router\DeleteFirewallPolicies;
use App\Jobs\Router\DeployRouterDefaultRule;
use App\Jobs\Router\DeployRouterLocale;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\Router;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteFirewallPoliciesTest extends TestCase
{
    use DatabaseMigrations;

    protected Router $router;
    protected FirewallPolicy $fwp1;
    protected FirewallPolicy $fwp2;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testSucceeds()
    {
        $this->markTestSkipped("Broken until firewall policies are moved to new sync");

        Model::withoutEvents(function() {
            $this->fwp1 = factory(FirewallPolicy::class)->create([
                'id' => "fwp-test1",
                'router_id' => $this->router()->id,
            ]);
            $this->fwp2 = factory(FirewallPolicy::class)->create([
                'id' => "fwp-test2",
                'router_id' => $this->router()->id,
            ]);
        });

        $router = Router::findOrFail($this->router()->id);

        dispatch(new DeleteFirewallPolicies($router));

        Event::assertNotDispatched(JobFailed::class);
        $this->assertNotNull($this->fwp1->deleted_at);
        $this->assertNotNull($this->fwp2->deleted_at);
    }
}
