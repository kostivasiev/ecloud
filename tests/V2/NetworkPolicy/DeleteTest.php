<?php
namespace Tests\V2\NetworkPolicy;

use App\Events\V2\NetworkPolicy\Deleting;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class DeleteTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testDeleteResource()
    {
        Event::fake();

        $this->delete(
            '/v2/network-policies/' . $this->networkPolicy()->id,
            []
        )->assertResponseStatus(202);

        Event::assertDispatched(Deleting::class);
    }
}