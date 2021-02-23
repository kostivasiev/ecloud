<?php

namespace Tests\V2\Instances;

use App\Models\V2\Instance;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();

        $this->kingpinServiceMock()->shouldReceive('delete')
            ->withArgs(['/api/v2/vpc/' . $this->instance()->vpc->id . '/instance/' . $this->instance()->id . '/power'])
            ->andReturn(
                new Response(200)
            );
        $this->kingpinServiceMock()->shouldReceive('delete')
            ->withArgs(['/api/v2/vpc/' . $this->instance()->vpc->id . '/instance/' . $this->instance()->id])
            ->andReturn(
                new Response(200)
            );
    }

    public function testSuccessfulDelete()
    {
        $this->delete('/v2/instances/' . $this->instance()->id, [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(204);
        $this->instance()->refresh();
        $this->assertNotNull($this->instance()->deleted_at);
    }

    public function testAdminInstanceLocking()
    {
        // Lock the instance
        $this->instance()->locked = true;
        $this->instance()->save();
        $this->delete(
            '/v2/instances/' . $this->instance()->id,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);
        $instance = Instance::withTrashed()->findOrFail($this->instance()->id);
        $this->assertNotNull($instance->deleted_at);
    }

    public function testNonAdminInstanceLocking()
    {
        // First lock the instance
        $this->instance()->locked = true;
        $this->instance()->save();
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
        $this->instance()->save();
        $this->delete(
            '/v2/instances/' . $this->instance()->id,
            [],
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);
        $instance = Instance::withTrashed()->findOrFail($this->instance()->id);
        $this->assertNotNull($instance->deleted_at);
    }
}
