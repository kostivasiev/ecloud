<?php

namespace Tests\unit\Jobs\LoadBalancerNetwork;

use App\Events\V2\Task\Created;
use App\Jobs\LoadBalancer\AddNetworks;
use App\Jobs\LoadBalancerNetwork\CreateNics;
use App\Models\V2\Network;
use App\Models\V2\Nic;
use App\Support\Sync;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;

class CreateNicsTest extends TestCase
{
    use LoadBalancerMock;

    public function testInstanceDosNotHaveNicOnSameNetworkCreates()
    {
        Event::fake([JobFailed::class, Created::class, JobProcessed::class]);

        $this->assertCount(0, $this->loadBalancerInstance()->nics);

        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/vpc/' . $this->vpc()->id . '/instance/' . $this->loadBalancerInstance()->id . '/nic',
                [
                    'json' => [
                        'networkId' => $this->loadBalancerNetwork()->network_id,
                    ],
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'macAddress' => '00:50:56:8a:eb:f2'
                ]));
            });

        $task = $this->createSyncUpdateTask($this->loadBalancerNetwork());

        dispatch(new CreateNics($task));

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_UPDATE;
        });

        $this->assertCount(1, $this->loadBalancerInstance()->refresh()->nics);

        $nic = $this->loadBalancerInstance()->nics->first();

        $this->assertEquals('00:50:56:8a:eb:f2', $nic->mac_address);
        $this->assertEquals($this->loadBalancerNetwork()->network_id, $nic->network_id);

        $task->refresh();

        $this->assertNotNull($task->data['nic_ids']);

        Event::assertNotDispatched(JobFailed::class);

        $event = Event::dispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_UPDATE;
        })->first()[0];
        $event->model->setAttribute('completed', true)->saveQuietly();

        dispatch(new CreateNics($task));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testReleasedWhenSyncing()
    {
        Event::fake([JobFailed::class, Created::class, JobProcessed::class]);

        $this->assertCount(0, $this->loadBalancerInstance()->nics);

        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/vpc/' . $this->vpc()->id . '/instance/' . $this->loadBalancerInstance()->id . '/nic',
                [
                    'json' => [
                        'networkId' => $this->loadBalancerNetwork()->network_id,
                    ],
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'macAddress' => '00:50:56:8a:eb:f2'
                ]));
            });

        $task = $this->createSyncUpdateTask($this->loadBalancerNetwork());

        dispatch(new CreateNics($task));

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_UPDATE;
        });

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }

    public function testInstanceHasNicOnSameNetworkSkips()
    {
        Nic::withoutEvents(function () {
            $nic = factory(Nic::class)->create([
                'id' => 'nic-test',
                'mac_address' => 'AA:BB:CC:DD:EE:FF',
            ]);
            $nic->network()->associate($this->network());
            $this->loadBalancerInstance()->nics()->save($nic);
        });

        $this->assertCount(1, $this->loadBalancerInstance()->nics);

        $task = $this->createSyncUpdateTask($this->loadBalancerNetwork());

        $this->kingpinServiceMock()->shouldNotReceive('post');

        dispatch(new CreateNics($task));

        $this->assertCount(1, $this->loadBalancerInstance()->nics);

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testInstanceHasNicsOnDifferentNetworksCreates()
    {
        Event::fake([JobFailed::class, Created::class]);

        $network = Model::withoutEvents(function () {
            return factory(Network::class)->create([
                'id' => 'net-abc',
                'subnet' => '10.0.0.0/24',
                'router_id' => $this->router()->id
            ]);
        });

        Nic::withoutEvents(function () use ($network) {
            $nic = factory(Nic::class)->create([
                'id' => 'nic-test',
                'mac_address' => 'AA:BB:CC:DD:EE:FF',
            ]);
            $nic->network()->associate($network);
            $this->loadBalancerInstance()->nics()->save($nic);
        });

        $this->assertCount(1, $this->loadBalancerInstance()->nics);

        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/vpc/' . $this->vpc()->id . '/instance/' . $this->loadBalancerInstance()->id . '/nic',
                [
                    'json' => [
                        'networkId' => $this->loadBalancerNetwork()->network_id,
                    ],
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'macAddress' => '00:50:56:8a:eb:f2'
                ]));
            });

        $task = $this->createSyncUpdateTask($this->loadBalancerNetwork());

        dispatch(new CreateNics($task));

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == 'sync_update';
        });

        $this->assertCount(2, $this->loadBalancerInstance()->refresh()->nics);

        Event::assertNotDispatched(JobFailed::class);
    }
}
