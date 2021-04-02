<?php

namespace Tests\unit\Jobs\Router;

use App\Jobs\Router\DeployRouterDefaultRule;
use App\Jobs\Router\DeployRouterLocale;
use App\Models\V2\Router;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeployRouterDefaultRuleTest extends TestCase
{
    use DatabaseMigrations;

    protected Router $router;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testSucceeds()
    {
        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('policy/api/v1/infra/domains/default/gateway-policies/Policy_Default_Infra-tier1-' . $this->router()->id . '/rules/default_rule')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'action' => 'ALLOW',
                    'some_other_property' => 'some value',
                    '_excluded' => 'to be excluded'
                ]));
            });

        $this->nsxServiceMock()->expects('patch')
            ->withArgs([
                'policy/api/v1/infra/domains/default/gateway-policies/Policy_Default_Infra-tier1-' . $this->router()->id . '/rules/default_rule',
                [
                    'json' => [
                        'action' => 'REJECT',
                        'some_other_property' => 'some value',
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new DeployRouterDefaultRule($this->router()));

        Event::assertNotDispatched(JobFailed::class);
    }
}
