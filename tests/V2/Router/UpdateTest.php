<?php

namespace Tests\V2\Router;

use App\Events\V2\Task\Created;
use App\Models\V2\Router;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    public function testValidDataIsSuccessful()
    {
        Event::fake(Created::class);

        $this->patch(
            '/v2/routers/' . $this->router()->id,
            [
                'name' => 'expected',
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertStatus(202);
        $this->assertEquals('expected', Router::findOrFail($this->router()->id)->name);
    }
}
