<?php
namespace Tests\V2\NetworkPolicy;

use App\Events\V2\NetworkPolicy\Saved;
use App\Events\V2\NetworkPolicy\Saving;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testUpdateResource()
    {
        Event::fake();

        $this->patch(
            '/v2/network-policies/' . $this->networkPolicy()->id,
            [
                'name' => 'New Policy Name',
            ]
        )->seeInDatabase(
            'network_policies',
            [
                'id' => $this->networkPolicy()->id,
                'name' => 'New Policy Name',
            ],
            'ecloud'
        )->assertResponseStatus(202);

        Event::assertDispatched(Saving::class);
        Event::assertDispatched(Saved::class);
    }
}
