<?php

namespace Tests\V2\Nic;

use App\Events\V2\Task\Created;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class UpdateTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->be((new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
    }

    public function testValidDataIsSuccessful()
    {
        Event::fake([Created::class]);
        $this->patch('/v2/nics/' . $this->nic()->id, ['name' => 'renamed'])
            ->assertStatus(202);
        $this->assertDatabaseHas(
            'nics',
            [
                'id' => $this->nic()->id,
                'name' => 'renamed'
            ],
            'ecloud'
        );
        Event::assertDispatched(Created::class);
    }
}
