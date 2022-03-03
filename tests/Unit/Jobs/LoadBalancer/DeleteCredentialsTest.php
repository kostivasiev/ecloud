<?php

namespace Tests\Unit\Jobs\LoadBalancer;

use App\Events\V2\Task\Created;
use App\Jobs\LoadBalancer\DeleteCredentials;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;

class DeleteCredentialsTest extends TestCase
{
    use LoadBalancerMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->loadBalancer()
            ->credentials()
            ->createMany([
                [
                    'name' => 'keepalived',
                    'resource_id' => $this->loadBalancer()->id,
                    'host' => null,
                    'username' => 'keepalived',
                    'password' => 'randomPasswordHere',
                    'port' => null,
                    'is_hidden' => true,
                ],
                [
                    'name' => 'haproxy stats',
                    'resource_id' => $this->loadBalancer()->id,
                    'host' => null,
                    'username' => 'ukfast_stats',
                    'password' => 'abcdefgh',
                    'port' => 8090,
                    'is_hidden' => true,
                ]
            ]);
    }

    public function testCredentialsDeleted()
    {
        $task = Model::withoutEvents(function () {
            $task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_DELETE,
            ]);
            $task->resource()->associate($this->loadBalancer());
            $task->save();
            return $task;
        });
        Event::fake([JobFailed::class, Created::class]);
        dispatch(new DeleteCredentials($task));
        Event::assertNotDispatched(JobFailed::class);

        $this->assertEquals(0, $this->loadBalancer()->credentials()->count());
    }
}
