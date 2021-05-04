<?php

namespace Tests\V2\Router;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    public function testValidDataIsSuccessful()
    {
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
            ->assertResponseStatus(200);
        $this->assertEquals('expected', Router::findOrFail($this->router()->id)->name);
    }
}
