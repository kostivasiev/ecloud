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

        $this->delete('/v2/instances/' . $this->instance()->id, [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class);
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
                'detail' => 'The specified Instance is locked',
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
