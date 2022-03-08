<?php

namespace Tests\V2\Task;

use App\Events\V2\Task\Created;
use App\Models\V2\Region;
use App\Models\V2\Task;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class GetTest extends TestCase
{
    /** @var Region */
    private $region;

    /** @var Task */
    private $task1;
    private $task2;

    public function setUp(): void
    {
        parent::setUp();

        Event::fake([Created::class]);
        $this->task1 = new Task([
            'id' => 'sync-1',
            'name' => 'sync-1-task',
            'reseller_id' => 1,
        ]);
        $this->task1->resource()->associate($this->vpc());
        $this->task1->save();
        $this->task2 = new Task([
            'id' => 'sync-2',
            'name' => 'sync-2-task',
            'reseller_id' => 2,
        ]);
        $this->task2->resource()->associate($this->vpc());
        $this->task2->save();

    }

    public function testRetrievesTasksForResellerWhenScoped()
    {
        $this->get('/v2/tasks', [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertJsonFragment([
            'id' => $this->task1->id,
            'name' => $this->task1->name,
        ])->assertJsonMissing([
            'id' => $this->task2->id,
            'name' => $this->task2->name,
        ])->assertStatus(200);
    }

    public function testRetrievesAllTasksAsAdmin()
    {
        $this->get('/v2/tasks', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertJsonFragment([
            'id' => $this->task1->id,
            'name' => $this->task1->name,
        ])->assertJsonFragment([
            'id' => $this->task2->id,
            'name' => $this->task2->name,
        ])->assertStatus(200);
    }

    public function testGetNonOwnedTaskFails()
    {
        $this->get('/v2/tasks/' . $this->task2->id, [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertJsonMissing([
            'id' => $this->task2->id,
            'name' => $this->task2->name,
        ])->assertStatus(404);
    }

    public function testGetOwnedTaskSucceeds()
    {
        $this->get('/v2/tasks/' . $this->task1->id, [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertJsonFragment([
            'id' => $this->task1->id,
            'name' => $this->task1->name,
        ])->assertStatus(200);
    }
}
