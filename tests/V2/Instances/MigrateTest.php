<?php

namespace Tests\V2\Instances;

use App\Events\V2\Task\Created;
use App\Models\V2\Image;
use App\Models\V2\Instance;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class MigrateTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testMigrateToPrivate()
    {
        Event::fake(Created::class);

        $this->post(
            '/v2/instances/' . $this->instanceModel()->id . '/migrate',
            [
                'host_group_id' => $this->hostGroup()->id
            ],
        )
            ->assertResponseStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class);
    }

    public function testMigrateToPublic()
    {
        Event::fake(Created::class);

        $this->post('/v2/instances/' . $this->instanceModel()->id . '/migrate')
            ->assertResponseStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class);
    }

    public function testIsCompatiblePlatformFails()
    {
        Event::fake();

        $instance = Instance::withoutEvents(function() {
            return factory(Instance::class)->create([
                'id' => 'i-' . uniqid(),
                'vpc_id' => $this->vpc()->id,
                'name' => 'Test Instance ' . uniqid(),
            ]);
        });

        factory(Image::class)->create([
            'id' => 'img-' . uniqid(),
            'platform' => 'Windows'
        ])->instances()->save($instance);


        $this->hostGroup()->windows_enabled = false;
        $this->hostGroup()->saveQuietly();

        $this->post(
            '/v2/instances/' . $instance->id . '/migrate',
            [
                'host_group_id' => $this->hostGroup()->id
            ],
        )
            ->assertResponseStatus(422);
    }
}
