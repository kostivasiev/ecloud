<?php

namespace Tests\V2\RouterThroughput;

use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class DeleteTest extends TestCase
{
    private Consumer $consumer;

    public function setUp(): void
    {
        parent::setUp();
        $this->consumer = new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']);
        $this->consumer->setIsAdmin(true);
    }

    public function testSuccessfulDelete()
    {
        $this->be($this->consumer);

        $this->delete('/v2/router-throughputs/' . $this->routerThroughput()->id)
            ->assertStatus(204);

        $this->routerThroughput()->refresh();
        $this->assertNotNull($this->routerThroughput()->deleted_at);
    }

    public function testFailDeleteIfRouterAttached()
    {
        $this->be($this->consumer);
        $this->router();

        $this->delete('/v2/router-throughputs/' . $this->routerThroughput()->id)
            ->assertJsonFragment(
                [
                    'title' => 'Precondition Failed',
                    'detail' => 'The specified resource has dependant relationships and cannot be deleted: ' . $this->router()->id,
                ]
            )->assertStatus(412);
    }
}
