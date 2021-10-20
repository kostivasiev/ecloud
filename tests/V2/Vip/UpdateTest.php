<?php

namespace Tests\V2\Vip;

use App\Events\V2\Task\Created;
use App\Models\V2\Vip;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testValidDataIsSuccessful()
    {
        Event::fake(Created::class);
        $data = [
            'ip_address_id' => "vip-bbbbbbbb",
        ];
        $this->patch('/v2/vips/' . $this->vip()->id,
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(202);
        Event::assertDispatched(\App\Events\V2\Task\Created::class);
        $this->assertEquals($data['ip_address_id'], Vip::findOrFail($this->vip()->id)->ip_address_id);
    }
}
