<?php

namespace Tests\V2\Instances;

use App\Models\V2\Instance;
use App\Models\V2\Task;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testSuccessfulDelete()
    {
        Event::fake();

        $this->delete('/v2/instances/' . $this->instance()->id, [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(202);
        $this->instance()->refresh();
        $this->assertNotNull($this->instance()->deleted_at);
    }

    public function testAdminInstanceLocking()
    {
        Event::fake(\App\Events\V2\Task\Created::class);
        // Lock the instance
        $this->instance()->locked = true;
        $this->instance()->saveQuietly();

        $this->delete(
            '/v2/instances/' . $this->instance()->id,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class);
    }

    public function testNonAdminInstanceLocking()
    {
        Event::fake(\App\Events\V2\Task\Created::class);

        // First lock the instance
        $this->instance()->locked = true;
        $this->instance()->saveQuietly();

        $this->delete(
            '/v2/instances/' . $this->instance()->id,
            [],
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title' => 'Forbidden',
                'detail' => 'The specified instance is locked',
                'status' => 403,
            ])
            ->assertResponseStatus(403);
        // Now unlock the instance
        $this->instance()->locked = false;
        $this->instance()->saveQuietly();

        $this->delete(
            '/v2/instances/' . $this->instance()->id,
            [],
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class);
    }
}
