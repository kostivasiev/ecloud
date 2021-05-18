<?php
namespace Tests\V2\Image;

use App\Models\V2\Image;
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
        $this->delete('/v2/images/' . $this->image()->id,)->assertResponseStatus(403);
    }

    public function testAdminDeletePrivateSucceeds()
    {
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        factory(Image::class)->create([
            'id' => 'img-private-test',
            'reseller_id' => 1,
            'public' => false
        ]);

        Event::fake(\App\Events\V2\Task\Created::class);

        $this->delete('/v2/images/img-private-test')->assertResponseStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class);
    }

    public function testNotAdminDeletePrivateIsOwnerSucceeds()
    {
        factory(Image::class)->create([
            'id' => 'img-private-test',
            'reseller_id' => 1,
            'public' => false
        ]);

        Event::fake(\App\Events\V2\Task\Created::class);

        Event::fake(\App\Events\V2\Task\Created::class);

        $this->delete('/v2/images/img-private-test')->assertResponseStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class);
    }

    public function testNotAdminDeletePrivateNotOwnerFails()
    {
        factory(Image::class)->create([
            'id' => 'img-private-test',
            'reseller_id' => 2,
            'public' => false
        ]);

        $this->delete('/v2/images/img-private-test')->assertResponseStatus(404);
    }
}