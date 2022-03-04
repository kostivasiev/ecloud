<?php

namespace Tests\unit\Jobs\LoadBalancer;

use App\Events\V2\Task\Created;
use App\Jobs\LoadBalancer\ConfigureAntiAffinity;
use App\Jobs\LoadBalancer\ConfigurePeers;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;
use UKFast\Admin\Loadbalancers\AdminClient;
use UKFast\Admin\Loadbalancers\AdminClusterClient;

class ConfigureAntiAffinityTest extends TestCase
{
    use LoadBalancerMock;

    private $task;

    public function testNonHaReturns()
    {
        Event::fake([JobFailed::class, Created::class]);

        $task = $this->createSyncUpdateTask($this->loadBalancer());
        $this->loadBalancerNode();

        dispatch(new ConfigureAntiAffinity($task));
        Event::assertNotDispatched(JobFailed::class);
    }

    public function testCreatesWhenDoesntExist()
    {
        Event::fake([JobFailed::class, Created::class]);

        $task = $this->createSyncUpdateTask($this->loadBalancer());
        $this->loadBalancerHANodes();

        $this->kingpinServiceMock()->expects('get')
            ->withArgs([
                '/api/v2/vpc/' . $this->vpc()->id . '/instance/' . $this->loadBalancerHANodes[0]->instance->id
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'hostGroupID' => 'hg-test'
                ]));
            });

        $this->kingpinServiceMock()->expects('get')
            ->withArgs([
                '/api/v2/hostgroup/hg-test/constraint'
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    [
                        'ruleName' => 'somerule'
                    ],
                ]));
            });

        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/hostgroup/hg-test/constraint/instance/separate',
                [
                    'json' => [
                        'ruleName' => $this->loadBalancer()->id,
                        'vpcId' => $this->vpc()->id,
                        'instanceIds' => collect($this->loadBalancerHANodes())->pluck('instance')->pluck('id')->toArray()
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
            });

        dispatch(new ConfigureAntiAffinity($task));
        Event::assertNotDispatched(JobFailed::class);
    }

    public function testDoesntCreateWhenExists()
    {
        Event::fake([JobFailed::class, Created::class]);

        $task = $this->createSyncUpdateTask($this->loadBalancer());
        $this->loadBalancerHANodes();

        $this->kingpinServiceMock()->expects('get')
            ->withArgs([
                '/api/v2/vpc/' . $this->vpc()->id . '/instance/' . $this->loadBalancerHANodes[0]->instance->id
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'hostGroupID' => 'hg-test'
                ]));
            });

        $this->kingpinServiceMock()->expects('get')
            ->withArgs([
                '/api/v2/hostgroup/hg-test/constraint'
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    [
                        'ruleName' => $this->loadBalancer()->id
                    ],
                ]));
            });

        dispatch(new ConfigureAntiAffinity($task));
        Event::assertNotDispatched(JobFailed::class);
    }
}
