<?php

namespace Tests\V2\Vip;

use App\Events\V2\Task\Created;
use App\Models\V2\Vip;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\VipMock;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class UpdateTest extends TestCase
{
    use VipMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testValidDataIsSuccessful()
    {
        Event::fake(Created::class);

        $this->patch('/v2/vips/' . $this->vip()->id,
            [
                'name' => 'foo',
            ]
        )->seeInDatabase(
            'vips',
            [
                'name' => 'foo',
            ],
            'ecloud'
        )->assertResponseStatus(202);
    }
}
