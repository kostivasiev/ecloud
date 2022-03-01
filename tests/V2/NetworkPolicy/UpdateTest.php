<?php
namespace Tests\V2\NetworkPolicy;

use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class UpdateTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testUpdateResource()
    {
        Event::fake(\App\Events\V2\Task\Created::class);
        $this->vpc()->advanced_networking = true;
        $this->vpc()->saveQuietly();

        $this->patch(
            '/v2/network-policies/' . $this->networkPolicy()->id,
            [
                'name' => 'New Policy Name',
            ]
        )->seeInDatabase(
            'network_policies',
            [
                'id' => 'np-test',
                'name' => 'New Policy Name',
            ],
            'ecloud'
        )->assertResponseStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class);
    }
}
