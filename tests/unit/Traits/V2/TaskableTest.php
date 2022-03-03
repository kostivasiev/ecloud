<?php

namespace Tests\unit\Traits\V2;

use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TaskableTest extends TestCase
{
    public function testResellerScopableModelSetsTaskResellerId()
    {
        Event::fake(\App\Events\V2\Task\Created::class);

        $model = new TaskableResellerScopableTestModel([
            'id' => 'test-testing',
        ]);
        $model->resellerId = 123;

        $task = $model->createTask('test_tast', 'test_job', ['testKey' => 'testVal']);

        Event::assertDispatched(\App\Events\V2\Task\Created::class);

        $this->assertEquals('test_tast', $task->name);
        $this->assertEquals('test_job', $task->job);
        $this->assertEquals(['testKey' => 'testVal'], $task->data);
        $this->assertEquals(123, $task->reseller_id);
    }

    public function testNonResellerScopableModelSetsTaskResellerId()
    {
        Event::fake(\App\Events\V2\Task\Created::class);

        $model = new TaskableTestModel([
            'id' => 'test-testing',
        ]);

        $task = $model->createTask('test_tast', 'test_job', ['testKey' => 'testVal']);

        Event::assertDispatched(\App\Events\V2\Task\Created::class);

        $this->assertEquals('test_tast', $task->name);
        $this->assertEquals('test_job', $task->job);
        $this->assertEquals(['testKey' => 'testVal'], $task->data);
        $this->assertNull($task->reseller_id);
    }
}
