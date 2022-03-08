<?php

namespace Tests\V2\Network;

use App\Events\V2\Task\Created;
use App\Models\V2\Network;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class UpdateTest extends TestCase
{
    public function testValidDataIsSuccessful()
    {
        Event::fake(Created::class);

        $this->asAdmin()
            ->patch(
                '/v2/networks/' . $this->network()->id,
                [
                    'name' => 'expected',
                    'subnet' => '192.168.0.0/24'
                ]
            )->assertStatus(202);

        Event::assertDispatched(Created::class);

        $network = Network::findOrFail($this->network()->id);
        $this->assertEquals('expected', $network->name);
    }
}
