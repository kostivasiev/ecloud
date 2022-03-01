<?php

namespace Tests\V2\Network;

use App\Models\V2\Network;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    public function testValidDataIsSuccessful()
    {
        $this->patch(
            '/v2/networks/' . $this->network()->id,
            [
                'name' => 'expected',
                'subnet' => '192.168.0.0/24'
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertResponseStatus(202);

        $network = Network::findOrFail($this->network()->id);
        $this->assertEquals('expected', $network->name);
    }
}
