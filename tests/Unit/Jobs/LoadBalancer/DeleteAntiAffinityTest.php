<?php

namespace Tests\unit\Jobs\LoadBalancer;

use App\Events\V2\Task\Created;
use App\Jobs\LoadBalancer\DeleteAntiAffinity;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;

class DeleteAntiAffinityTest extends TestCase
{
    use LoadBalancerMock;

    public function testNonHaReturns()
    {
        Event::fake([JobFailed::class, Created::class]);

        $task = $this->createSyncDeleteTask($this->loadBalancer());
        $this->loadBalancerNode();

        dispatch(new DeleteAntiAffinity($task));
        Event::assertNotDispatched(JobFailed::class);
    }

    public function testSkipsWhenDoesntExist()
    {
        Event::fake([JobFailed::class, Created::class]);

        $task = $this->createSyncDeleteTask($this->loadBalancer());
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

        dispatch(new DeleteAntiAffinity($task));
        Event::assertNotDispatched(JobFailed::class);
    }

    public function testDeletesWhenExists()
    {
        Event::fake([JobFailed::class, Created::class]);

        $task = $this->createSyncDeleteTask($this->loadBalancer());
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

        $this->kingpinServiceMock()->expects('delete')
            ->withArgs([
                '/api/v2/hostgroup/hg-test/constraint/' . $this->loadBalancer()->id
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
            });

        dispatch(new DeleteAntiAffinity($task));
        Event::assertNotDispatched(JobFailed::class);
    }
}
