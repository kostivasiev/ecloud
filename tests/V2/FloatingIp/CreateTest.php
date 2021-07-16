<?php

namespace Tests\V2\FloatingIp;

use App\Support\Sync;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CreateTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testSuccess()
    {
        Event::fake([\App\Events\V2\Task\Created::class]);

        $data = [
            'vpc_id' => $this->vpc()->id
        ];

        $this->post('/v2/floating-ips', $data)
            ->seeInDatabase('floating_ips', $data, 'ecloud')
            ->assertResponseStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_UPDATE;
        });
    }
}
