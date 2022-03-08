<?php

namespace Tests\V2\Nic;

use App\Events\V2\Task\Created;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class DeleteTest extends TestCase
{
    public function testValidNicSucceeds()
    {
        Event::fake([Created::class]);

        $this->be((new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        Event::fake([Created::class]);

        $this->delete('/v2/nics/' . $this->nic()->id)
            ->assertStatus(202);

        Event::assertDispatched(Created::class);
    }
}
