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

    private Task $task;

    public function setUp(): void
    {
        parent::setUp();
        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->loadBalancer());
            $this->task->save();
        });
    }

    public function testCredentialsAreCreated()
    {
        $this->loadBalancer()->setAttribute('config_id', 11111)->saveQuietly();

        Event::fake([JobFailed::class, Created::class]);

        dispatch(new CreateCredentials($this->task));
        Event::assertNotDispatched(JobFailed::class);

        $this->assertGreaterThan(0, $this->loadBalancer()->credentials()->count());

        // Now verify that the credentials have been created
        $keepAliveD = $this->loadBalancer()
            ->credentials()
            ->where('username', '=', 'keepalived')
            ->first();
        $this->assertNotNull($keepAliveD->password);

        $statsCredentials = $this->loadBalancer()
            ->credentials()
            ->where('username', '=', 'ukfast_stats')
            ->first();
        $this->assertNotNull($statsCredentials->password);
    }

    public function testCredentialsNotCreated()
    {
        Event::fake([JobFailed::class, Created::class]);

        $this->loadBalancer()->credentials()->createMany([
            [
                'username' => 'keepalived',
            ],
            [
                'username' => 'ukfast_stats',
            ]
        ]);

        $this->assertEquals(2, $this->loadBalancer()->credentials()->count());

        dispatch(new CreateCredentials($this->task));
        Event::assertNotDispatched(JobFailed::class);

        $this->assertEquals(2, $this->loadBalancer()->credentials()->count());
    }
}
