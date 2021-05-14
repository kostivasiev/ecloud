<?php

namespace Tests\V2\Task;

use App\Events\V2\Task\Created;
use App\Models\V2\Region;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class GetTest extends TestCase
{
    /** @var Region */
    private $region;

    /** @var Task */
    private $task;

    public function setUp(): void
    {
        parent::setUp();

        Event::fake([Created::class]);
        $this->task = new Task([
            'id' => 'sync-1',
            'name' => Sync::TASK_NAME_UPDATE,
        ]);
        $this->task->resource()->associate($this->vpc());
        $this->task->save();

    }

    public function testNotAdminGetCollectionFails()
    {
        $this->get('/v2/tasks', [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'title' => 'Unauthorized',
            'detail' => 'Unauthorized',
            'status' => 401,
        ])->assertResponseStatus(401);
    }

    public function testAdminGetCollectionSucceeds()
    {
        $this->get('/v2/tasks', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => $this->task->id,
            'name' => $this->task->name,
        ])->assertResponseStatus(200);
    }

    public function testNotAdminGetItemFails()
    {
        $this->get('/v2/tasks/' . $this->task->id, [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'title' => 'Unauthorized',
            'detail' => 'Unauthorized',
            'status' => 401,
        ])->assertResponseStatus(401);
    }

    public function testAdminGetItemSucceeds()
    {
        $this->get('/v2/tasks/' . $this->task->id, [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => $this->task->id,
            'name' => $this->task->name,
        ])->assertResponseStatus(200);
    }
}
