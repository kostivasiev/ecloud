<?php
namespace Tests\V2\Image;

use App\Models\V2\Image;
use App\Models\V2\Vpc;
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

    public function testAdminDeletePublicSucceeds()
    {
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        Event::fake(\App\Events\V2\Task\Created::class);

        $this->delete('/v2/images/' . $this->image()->id,)->assertResponseStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class);
    }

    public function testNotAdminDeletePublicFails()
    {
        $this->delete('/v2/images/' . $this->image()->id)->assertResponseStatus(403);
    }

    public function testAdminDeletePrivateSucceeds()
    {
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        Image::factory()->create([
            'id' => 'img-private-test',
            'vpc_id' => $this->vpc()->id,
            'visibility' => Image::VISIBILITY_PRIVATE
        ]);

        Event::fake(\App\Events\V2\Task\Created::class);

        $this->delete('/v2/images/img-private-test')->assertResponseStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class);
    }

    public function testNotAdminDeletePrivateIsOwnerSucceeds()
    {
        Image::factory()->create([
            'id' => 'img-private-test',
            'vpc_id' => $this->vpc()->id,
            'visibility' => Image::VISIBILITY_PRIVATE
        ]);

        Event::fake(\App\Events\V2\Task\Created::class);

        Event::fake(\App\Events\V2\Task\Created::class);

        $this->delete('/v2/images/img-private-test')->assertResponseStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class);
    }

    public function testNotAdminDeletePrivateNotOwnerFails()
    {
        Event::fake();
        $vpc = Vpc::factory()->create([
            'id' => 'vpc-' . uniqid(),
            'reseller_id' => 2
        ]);

        Image::factory()->create([
            'id' => 'img-private-test',
            'vpc_id' => $vpc->id,
            'visibility' => Image::VISIBILITY_PRIVATE
        ]);

        $this->delete('/v2/images/img-private-test')->assertResponseStatus(404);
    }
}