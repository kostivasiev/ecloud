<?php

namespace Tests\unit\Jobs\LoadBalancer;

use App\Events\V2\Task\Created;
use App\Jobs\LoadBalancer\CreateCredentials;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Mocks\Resources\LoadBalancerMock;

class CreateCredentialsTest extends TestCase
{
    use LoadBalancerMock;

    public function testCredentialsAreCreated()
    {
        $this->loadBalancer()->setAttribute('config_id', 11111)->saveQuietly();

        $task = Model::withoutEvents(function () {
            $task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($this->loadBalancer());
            $task->save();
            return $task;
        });
        Event::fake([JobFailed::class, Created::class]);

        dispatch(new CreateCredentials($task));
        Event::assertNotDispatched(JobFailed::class);

        $this->assertGreaterThan(0, $this->loadBalancer()->credentials()->count());

        // Now verify that the credentials have been created
        $keepAliveD = $this->loadBalancer()
            ->credentials()
            ->where('name', '=', 'keepalived')
            ->first();
        $this->assertNotNull($keepAliveD->password);

        $statsCredentials = $this->loadBalancer()
            ->credentials()
            ->where('name', '=', 'haproxy stats')
            ->first();
        $this->assertNotNull($statsCredentials->password);
    }

    public function testCredentialsNotCreated()
    {
        $this->loadBalancer()->setAttribute('config_id', null)->saveQuietly();
        $task = Model::withoutEvents(function () {
            $task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($this->loadBalancer());
            $task->save();
            return $task;
        });
        Event::fake([JobFailed::class, Created::class]);

        dispatch(new CreateCredentials($task));
        Event::assertNotDispatched(JobFailed::class);

        $this->assertEquals(0, $this->loadBalancer()->credentials()->count());
    }
}
