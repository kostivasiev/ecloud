<?php
namespace Tests\V2\NetworkPolicy;

use App\Events\V2\Task\Created;
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
        Event::fake(Created::class);
        $this->vpc()->advanced_networking = true;
        $this->vpc()->saveQuietly();

        $this->delete('/v2/network-policies/' . $this->networkPolicy()->id)
            ->assertStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class);
    }

    public function testNonAdminCannotDeleteLockedResource()
    {
        $this->networkPolicy()->setAttribute('locked', true)->saveQuietly();
        $this->asUser()
            ->delete('/v2/network-policies/' . $this->networkPolicy()->id)
            ->assertJsonFragment([
                'title' => 'Forbidden',
                'detail' => 'The specified resource is locked',
                'status' => 403,
            ])->assertStatus(403);
    }

    public function testAdminCanDeleteLockedResource()
    {
        Event::fake(Created::class);
        $this->networkPolicy()->setAttribute('locked', true)->saveQuietly();
        $this->asAdmin()
            ->delete('/v2/network-policies/' . $this->networkPolicy()->id)
            ->assertStatus(202);
    }
}