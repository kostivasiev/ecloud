<?php

namespace Tests\V2\Credential;

use Tests\TestCase;

class CreateTest extends TestCase
{
    public function testValidDataSucceeds()
    {
        $this->post(
            '/v2/credentials',
            [
                'resource_id' => 'abc-abc132',
                'host' => 'https://127.0.0.1',
                'username' => 'someuser',
                'password' => 'somepassword',
                'port' => 8080
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertStatus(201);

        $this->assertDatabaseMissing(
            'credentials',
            ['password' => 'somepassword'],
            'ecloud'
        );

        $this->assertDatabaseHas(
            'credentials',
            [
                'resource_id' => 'abc-abc132',
                'host' => 'https://127.0.0.1',
                'username' => 'someuser',
                'port' => 8080
            ],
            'ecloud'
        );
    }
}
