<?php

namespace Tests\unit\Jobs\Nsx\NetworkPolicy\SecurityGroup;

use App\Jobs\Nsx\NetworkPolicy\SecurityGroup\Deploy;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeployTest extends TestCase
{
    public function testDeploys()
    {
        $this->nsxServiceMock()->shouldReceive('patch')
            ->withArgs([
                '/policy/api/v1/infra/domains/default/groups/' . $this->networkPolicy()->id,
                [
                    'json' => [
                        'id' => $this->networkPolicy()->id,
                        'display_name' => $this->networkPolicy()->id,
                        'resource_type' => 'Group',
                        'expression' => [
                            [
                                'resource_type' => 'PathExpression',
                                'paths' => [
                                    '/infra/tier-1s/' . $this->router()->id . '/segments/' . $this->network()->id
                                ]
                            ]
                        ]
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });

        Event::fake([JobFailed::class]);

        dispatch(new Deploy($this->networkPolicy()));

        Event::assertNotDispatched(JobFailed::class);
    }
}
