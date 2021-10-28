<?php

namespace Tests\V2\Vip;

use App\Events\V2\Task\Created;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\VipMock;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class DeleteTest extends TestCase
{
    use VipMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testFailInvalidId()
    {
        $this->delete('/v2/vips/NOT_FOUND')
            ->seeJson([
                'title' => 'Not found',
                'detail' => 'No Vip with that ID was found',
                'status' => 404,
            ])
            ->assertResponseStatus(404);
    }

    public function testSuccessfulDelete()
    {
        Event::fake(Created::class);

        $this->delete('/v2/vips/' . $this->vip()->id,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(202);

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == 'sync_delete';
        });
    }
}
