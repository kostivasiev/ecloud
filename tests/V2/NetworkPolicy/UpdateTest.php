<?php
namespace Tests\V2\NetworkPolicy;

use App\Events\V2\Task\Created;
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
        )->assertStatus(202);
        $this->assertDatabaseHas(
            'network_policies',
            [
                'id' => 'np-test',
                'name' => 'New Policy Name',
            ],
            'ecloud'
        );

        Event::assertDispatched(\App\Events\V2\Task\Created::class);
    }

    public function testUserCannotUpdateLockedResource()
    {
        $this->networkPolicy()->setAttribute('locked', true)->saveQuietly();
        $this->asUser()
            ->patch(
                '/v2/network-policies/' . $this->networkPolicy()->id,
                [
                    'name' => 'New Policy Name',
                ]
            )->assertJsonFragment([
                'title' => 'Forbidden',
                'detail' => 'The specified resource is locked',
                'status' => 403,
            ])->assertStatus(403);
    }

    public function testAdminCanUpdateLockedResource()
    {
        Event::fake(Created::class);
        $this->networkPolicy()->setAttribute('locked', true)->saveQuietly();
        $this->asAdmin()
            ->patch(
                '/v2/network-policies/' . $this->networkPolicy()->id,
                [
                    'name' => 'New Policy Name',
                ]
            )->assertStatus(202);
    }
}
