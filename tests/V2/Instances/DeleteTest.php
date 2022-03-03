<?php

namespace Tests\V2\Instances;

use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testSuccessfulDelete()
    {
        Event::fake(\App\Events\V2\Task\Created::class);

        $this->delete('/v2/instances/' . $this->instanceModel()->id, [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class);
    }

    public function testAdminInstanceLocking()
    {
        Event::fake(\App\Events\V2\Task\Created::class);
        // Lock the instance
        $this->instanceModel()->locked = true;
        $this->instanceModel()->saveQuietly();

        $this->delete(
            '/v2/instances/' . $this->instanceModel()->id,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class);
    }

    public function testNonAdminInstanceLocking()
    {
        Event::fake(\App\Events\V2\Task\Created::class);

        // First lock the instance
        $this->instanceModel()->locked = true;
        $this->instanceModel()->saveQuietly();

        $this->delete(
            '/v2/instances/' . $this->instanceModel()->id,
            [],
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertJsonFragment([
                'title' => 'Forbidden',
                'detail' => 'The specified Instance is locked',
                'status' => 403,
            ])
            ->assertStatus(403);
        // Now unlock the instance
        $this->instanceModel()->locked = false;
        $this->instanceModel()->saveQuietly();

        $this->delete(
            '/v2/instances/' . $this->instanceModel()->id,
            [],
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class);
    }
}
